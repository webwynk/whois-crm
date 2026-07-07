<?php

declare(strict_types=1);

namespace WhoisCRM\Invoice;

use WhoisCRM\Database\Models\Invoice;
use WhoisCRM\Database\Models\Payment;
use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Package;
use WhoisCRM\Database\Models\ActivityLog;
use WhoisCRM\Notification\EmailManager;

/**
 * Invoice Orchestrator.
 *
 * Automatically generates database invoice records and PDF documents
 * after successful payments.
 */
class InvoiceGenerator
{
    private Invoice $invoice_model;
    private Payment $payment_model;
    private Customer $customer_model;
    private Subscription $sub_model;
    private Package $pkg_model;
    private PdfRenderer $pdf_renderer;

    public function __construct()
    {
        $this->invoice_model  = new Invoice();
        $this->payment_model  = new Payment();
        $this->customer_model = new Customer();
        $this->sub_model      = new Subscription();
        $this->pkg_model      = new Package();
        $this->pdf_renderer   = new PdfRenderer();

        // Hook automatic invoice generation when payment succeeds
        add_action('whoiscrm_payment_succeeded', [$this, 'generate_for_payment'], 10, 3);
    }

    /**
     * Generate an invoice for a specific payment.
     *
     * @param int $customer_id Customer record ID.
     * @param int $payment_id  Payment record ID.
     * @param int $sub_id      Subscription record ID.
     * @return int|false       The generated Invoice record ID on success, false on failure.
     */
    public function generate_for_payment(int $customer_id, int $payment_id, int $sub_id): int|false
    {
        // 1. Prevent duplicate invoice generation for the same payment
        $existing = $this->invoice_model->find_by('payment_id', $payment_id);
        if ($existing) {
            return (int) $existing->id;
        }

        $payment = $this->payment_model->find($payment_id);
        if (!$payment) {
            error_log(sprintf('[WHOISCRM Invoices] Payment record #%d not found.', $payment_id));
            return false;
        }

        $customer = $this->customer_model->get_with_user_data($customer_id);
        if (!$customer) {
            error_log(sprintf('[WHOISCRM Invoices] Customer record #%d not found.', $customer_id));
            return false;
        }

        $subscription = $this->sub_model->find($sub_id);
        $package      = $subscription ? $this->pkg_model->find((int) $subscription->package_id) : null;
        if (!$package) {
            error_log('[WHOISCRM Invoices] Package not found for subscription.');
            return false;
        }

        // 2. Generate Invoice Sequence Number
        $invoice_number = $this->generate_invoice_number();

        // 3. Extract billing parameters from Customer & Payment
        $billing_name    = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if (empty($billing_name)) {
            $billing_name = $customer->user_login ?? __('Customer', 'whois-crm');
        }

        $billing_company = $customer->company_name ?? '';
        $billing_address = $customer->billing_address ?? '';
        $billing_country = $customer->country_code ?? '';
        $billing_tax_id  = $customer->tax_id ?? '';

        // 4. Fetch seller settings
        $seller_name    = get_option('whoiscrm_company_name', get_bloginfo('name'));
        $seller_address = get_option('whoiscrm_company_address', '');
        $seller_tax_id  = get_option('whoiscrm_company_tax_id', '');
        $support_email  = get_option('whoiscrm_support_email', get_option('admin_email'));

        $billing_cycle = 'monthly';
        if ($subscription) {
            global $wpdb;
            $billing_cycle = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT billing_cycle FROM {$wpdb->prefix}whoiscrm_package_pricing WHERE id = %d",
                    $subscription->package_pricing_id
                )
            ) ?: 'monthly';
        }

        // Insert database invoice row
        $invoice_id = $this->invoice_model->insert([
            'payment_id'            => $payment_id,
            'customer_id'           => $customer_id,
            'invoice_number'        => $invoice_number,
            'invoice_date'          => current_time('mysql', true),
            'due_date'              => current_time('mysql', true),
            'billing_name'          => $billing_name,
            'billing_email'         => $customer->email ?? '',
            'billing_company'       => $billing_company,
            'billing_address'       => $billing_address,
            'billing_country'       => $billing_country,
            'billing_tax_id'        => $billing_tax_id,
            'seller_name'           => $seller_name,
            'seller_address'        => $seller_address,
            'seller_tax_id'         => $seller_tax_id,
            'line_item_description' => $package->name,
            'quantity'              => 1,
            'unit_price'            => $payment->subtotal,
            'subtotal'              => $payment->subtotal,
            'discount_description'  => $payment->coupon_id ? __('Coupon Discount', 'whois-crm') : null,
            'discount_amount'       => $payment->discount_amount,
            'taxable_amount'        => $payment->subtotal - $payment->discount_amount,
            'tax_label'             => get_option('whoiscrm_tax_label', 'Tax'),
            'tax_rate'              => $payment->tax_rate,
            'tax_amount'            => $payment->tax_amount,
            'total'                 => $payment->total_amount,
            'currency'              => $payment->currency,
            'payment_status'        => 'paid',
            'pdf_path'              => '',
        ]);

        if (!$invoice_id) {
            error_log('[WHOISCRM Invoices] Failed to insert database invoice row.');
            return false;
        }

        $invoice = $this->invoice_model->find((int) $invoice_id);

        // 5. Generate and save PDF document
        $pdf_html = $this->render_html_template($invoice, $payment, $package, $customer, $seller_name, $seller_address, $seller_tax_id, $support_email, $billing_cycle);
        
        $year     = date('Y');
        $rel_path = "invoices/{$year}/{$invoice_number}.pdf";
        $abs_path = WHOISCRM_DATA_DIR . $rel_path;

        if ($this->pdf_renderer->render_to_file($pdf_html, $abs_path)) {
            // Update relative PDF path in database
            $this->invoice_model->update((int) $invoice_id, [
                'pdf_path' => $rel_path,
            ]);
            $invoice->pdf_path = $rel_path;
        } else {
            error_log('[WHOISCRM Invoices] Failed to render PDF invoice file.');
        }

        // 6. Log activity
        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            sprintf(__('Generated Invoice %s for subscription renewal.', 'whois-crm'), $invoice_number),
            ['invoice_id' => $invoice_id, 'payment_id' => $payment_id],
            ActivityLog::SEVERITY_INFO,
            (int) $customer->user_id,
            'invoice',
            (int) $invoice_id
        );

        // 7. Fire custom actions (e.g., to email the PDF to customer)
        do_action('whoiscrm_invoice_generated', $invoice);

        // Trigger dispatcher to send the invoice email
        (new EmailManager())->send_invoice_attached($invoice);

        return $invoice_id;
    }

    /**
     * Generate the next auto-increment sequence invoice number for the year.
     */
    private function generate_invoice_number(): string
    {
        global $wpdb;
        $year       = date('Y');
        $table_name = $this->invoice_model->get_table();

        $max_invoice = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT invoice_number FROM {$table_name}
                 WHERE invoice_number LIKE %s
                 ORDER BY id DESC LIMIT 1",
                "WHOIS-{$year}-%"
            )
        );

        $seq = 1;
        if ($max_invoice) {
            $parts    = explode('-', $max_invoice);
            $last_seq = (int) end($parts);
            $seq      = $last_seq + 1;
        }

        return sprintf('WHOIS-%s-%s', $year, str_pad((string) $seq, 5, '0', STR_PAD_LEFT));
    }

    /**
     * Render the invoice template HTML.
     */
    private function render_html_template(
        object $invoice,
        object $payment,
        object $package,
        object $customer,
        string $company_name,
        string $company_address,
        string $company_tax_id,
        string $support_email,
        string $billing_cycle
    ): string {
        $file_path = WHOISCRM_PLUGIN_DIR . 'templates/invoices/invoice-pdf.php';

        if (!file_exists($file_path)) {
            return '';
        }

        ob_start();
        include $file_path;
        return ob_get_clean() ?: '';
    }
}
