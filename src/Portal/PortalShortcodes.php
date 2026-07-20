<?php

declare(strict_types=1);

namespace WhoisCRM\Portal;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Download;
use WhoisCRM\Database\Models\Invoice;
use WhoisCRM\Database\Models\Package;
use WhoisCRM\Database\Models\PackagePricing;
use WhoisCRM\Database\Models\ApiKey;

/**
 * Custom Customer Portal Shortcodes.
 *
 * Registers:
 *  [whoiscrm_portal]  - Private Customer Dashboard / Subscriptions / Downloads
 *  [whoiscrm_pricing] - Public Pricing Table
 */
class PortalShortcodes
{
    public function __construct()
    {
        add_shortcode('whoiscrm_portal',  [$this, 'render_portal']);
        add_shortcode('whoiscrm_pricing', [$this, 'render_pricing']);
    }

    /**
     * Render the customer portal interface.
     */
    public function render_portal(array $atts = []): string
    {
        if (!is_user_logged_in()) {
            return $this->get_login_redirect_notice();
        }

        $user_id = get_current_user_id();
        $customer = (new Customer())->find_by_user_id($user_id);

        if (!$customer) {
            // Logged-in WP user without CRM customer profile (e.g., admin). Create it.
            $customer_id = (new Customer())->insert([
                'user_id'   => $user_id,
                'is_active' => 1,
            ]);
            if ($customer_id !== false) {
                $customer = (new Customer())->find((int) $customer_id);
            }
        }

        if (!$customer) {
            return '<div class="whoiscrm-portal-alert whoiscrm-portal-alert--danger">' .
                esc_html__('Failed to load customer profile. Please contact support.', 'whois-crm') .
                '</div>';
        }

        if (empty($customer->is_active)) {
            return '<div class="whoiscrm-portal-alert whoiscrm-portal-alert--danger">' .
                esc_html__('Your account has been suspended. Please contact support.', 'whois-crm') .
                '</div>';
        }

        $tab = sanitize_key($_GET['tab'] ?? 'dashboard');
        
        // Output buffering since shortcodes must return HTML
        ob_start();
        
        $this->render_portal_layout($customer, $tab);
        
        return ob_get_clean();
    }

    /**
     * Render the outer portal wrapper and include the active tab.
     */
    private function render_portal_layout(object $customer, string $tab): void
    {
        $tabs = [
            'dashboard'     => __('Dashboard', 'whois-crm'),
            'downloads'     => __('Downloads', 'whois-crm'),
            'subscriptions' => __('Subscriptions', 'whois-crm'),
            'invoices'      => __('Invoices', 'whois-crm'),
            'profile'       => __('My Profile', 'whois-crm'),
        ];

        // Only show API Keys tab to Enterprise subscribers
        $has_enterprise = $this->has_active_enterprise((int) $customer->id);
        if ($has_enterprise) {
            $tabs['api-keys'] = __('API Access', 'whois-crm');
        }

        if (!array_key_exists($tab, $tabs)) {
            $tab = 'dashboard';
        }

        // Include Portal Layout wrapper
        $this->render_template('layout', [
            'customer'   => $customer,
            'tabs'       => $tabs,
            'active_tab' => $tab,
            'content'    => $this->get_tab_html($customer, $tab),
        ]);
    }

    /**
     * Get HTML for the active tab.
     */
    private function get_tab_html(object $customer, string $tab): string
    {
        ob_start();
        $customer_id = (int) $customer->id;

        switch ($tab) {
            case 'dashboard':
                $subscriptions = (new Subscription())->get_active_for_customer($customer_id);
                $downloads = (new Download())->get_for_customer($customer_id, 10);
                
                $total_downloads = 0;
                foreach ($downloads as $d) {
                    $total_downloads++;
                }

                $this->render_template('dashboard', [
                    'customer'      => $customer,
                    'subscriptions' => $subscriptions,
                    'downloads'     => $downloads,
                    'total_downloads' => $total_downloads,
                ]);
                break;

            case 'downloads':
                $page = max(1, (int) ($_GET['paged'] ?? 1));
                
                $filters = [
                    'service_type' => sanitize_key($_GET['service_type'] ?? ''),
                    'country_code' => strtoupper(sanitize_text_field($_GET['country_code'] ?? '')),
                    'tld'          => sanitize_text_field($_GET['tld'] ?? ''),
                ];

                $result = (new \WhoisCRM\Database\Models\DataFile())->get_accessible_for_customer($customer_id, $filters, $page, 15);
                
                $this->render_template('downloads', [
                    'files'        => $result['rows'],
                    'total'        => $result['total'],
                    'current_page' => $page,
                    'per_page'     => 15,
                    'filters'      => $filters,
                    'nonce'        => wp_create_nonce('whoiscrm_portal_nonce'),
                ]);
                break;

            case 'subscriptions':
                $subscriptions = (new Subscription())->get_all_for_customer($customer_id);
                $this->render_template('subscriptions', [
                    'subscriptions' => $subscriptions,
                    'nonce'         => wp_create_nonce('whoiscrm_portal_nonce'),
                ]);
                break;

            case 'invoices':
                $invoices = (new Invoice())->get_for_customer($customer_id);
                $this->render_template('invoices', [
                    'invoices' => $invoices,
                ]);
                break;

            case 'profile':
                $wp_user = get_userdata($customer->user_id);
                $this->render_template('profile', [
                    'customer' => $customer,
                    'wp_user'  => $wp_user,
                    'nonce'    => wp_create_nonce('whoiscrm_profile_nonce'),
                ]);
                break;

            case 'api-keys':
                if (!$this->has_active_enterprise($customer_id)) {
                    echo '<div class="whoiscrm-portal-alert whoiscrm-portal-alert--danger">' .
                        esc_html__('API Access is only available for Enterprise plans.', 'whois-crm') .
                        '</div>';
                } else {
                    $keys = (new ApiKey())->get_for_customer($customer_id);
                    $this->render_template('api-keys', [
                        'keys'  => $keys,
                        'nonce' => wp_create_nonce('whoiscrm_api_nonce'),
                    ]);
                }
                break;
        }

        return ob_get_clean();
    }

    /**
     * Render the public pricing table.
     */
    public function render_pricing(array $atts = []): string
    {
        $packages = (new Package())->get_active();

        // Auto-seed if database packages table is empty
        if (empty($packages)) {
            \WhoisCRM\Activator::seed_default_packages();
            $packages = (new Package())->get_active();
        }

        // Fallback: if database table is not seeded yet, parse default-packages.json directly
        if (empty($packages)) {
            $json_file = WHOISCRM_PLUGIN_DIR . 'data/default-packages.json';
            if (file_exists($json_file)) {
                $raw = json_decode((string) file_get_contents($json_file), false);
                if ($raw && !empty($raw->packages)) {
                    $packages = $raw->packages;
                }
            }
        }
        
        ob_start();
        $this->render_template('pricing-table', [
            'packages' => $packages,
            'nonce'    => wp_create_nonce('whoiscrm_checkout_nonce'),
        ]);
        return ob_get_clean();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    /**
     * Render a portal PHP template.
     */
    private function render_template(string $template, array $args = []): void
    {
        $file = WHOISCRM_PLUGIN_DIR . 'templates/portal/' . $template . '.php';
        
        if (file_exists($file)) {
            extract($args); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            include $file;
        } else {
            printf(
                /* translators: %s: template path */
                esc_html__('Template %s not found.', 'whois-crm'),
                esc_html($template)
            );
        }
    }

    /**
     * Check if a customer has an active enterprise plan subscription.
     */
    private function has_active_enterprise(int $customer_id): bool
    {
        $subscriptions = (new Subscription())->get_active_for_customer($customer_id);
        foreach ($subscriptions as $sub) {
            if ($sub->service_type === 'enterprise') {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns standard redirect notice when guest views private portal.
     */
    private function get_login_redirect_notice(): string
    {
        $login_page    = get_option('whoiscrm_login_page_id');
        $register_page = get_option('whoiscrm_register_page_id');

        $login_url    = $login_page ? get_permalink($login_page) : wp_login_url();
        $register_url = $register_page ? get_permalink($register_page) : wp_registration_url();
        $redirect     = add_query_arg('redirect_to', urlencode(get_permalink()), $login_url);

        return sprintf(
            '<!-- DM Sans Font Preload -->
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
            <div class="whoiscrm-portal-auth-notice">
                <div class="whoiscrm-portal-auth-icon-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h3>%1$s</h3>
                <p>%2$s</p>
                <div class="whoiscrm-portal-auth-actions">
                    <a href="%3$s" class="whoiscrm-portal-btn-auth-primary">
                        <span>%4$s</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                    <a href="%5$s" class="whoiscrm-portal-btn-auth-secondary">%6$s</a>
                </div>
            </div>',
            esc_html__('Authentication Required', 'whois-crm'),
            esc_html__('Please sign in to access your WHOIS data portal and downloads.', 'whois-crm'),
            esc_url($redirect),
            esc_html__('Sign In to Portal', 'whois-crm'),
            esc_url($register_url),
            esc_html__('Create an Account', 'whois-crm')
        );
    }
}
