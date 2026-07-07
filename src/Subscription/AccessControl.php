<?php

declare(strict_types=1);

namespace WhoisCRM\Subscription;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\DataFile;
use WhoisCRM\Database\Models\Subscription;

/**
 * Access Control service.
 *
 * Validates download and data access privileges for CRM customers.
 */
class AccessControl
{
    /**
     * Determine if a user (WordPress user ID) is allowed to download a data file.
     *
     * @param int $user_id      WordPress user ID.
     * @param int $data_file_id CRM DataFile ID.
     * @return bool
     */
    public function can_download(int $user_id, int $data_file_id): bool
    {
        if ($user_id <= 0) {
            return false;
        }

        $customer = (new Customer())->find_by_user_id($user_id);
        if (!$customer || !$customer->is_active) {
            return false;
        }

        $file = (new DataFile())->find($data_file_id);
        if (!$file || !$file->is_active) {
            return false;
        }

        // Retrieve active subscriptions
        $subscriptions = (new Subscription())->get_active_for_customer((int) $customer->id);

        foreach ($subscriptions as $sub) {
            // Enterprise plan grants access to all data files
            if ($sub->service_type === 'enterprise') {
                return true;
            }

            // Service type must match (e.g. whois_history, lead_generation, etc.)
            if ($sub->service_type !== $file->service_type) {
                continue;
            }

            // If package has no specific countries, it is global and covers all countries
            $pkg_countries = $sub->countries ? json_decode($sub->countries, true) : null;
            if (empty($pkg_countries)) {
                return true;
            }

            // Otherwise, check if target country is listed in the subscription
            if (is_array($pkg_countries) && in_array($file->country_code, $pkg_countries, true)) {
                return true;
            }
        }

        return false;
    }
}
