<?php

namespace App\Helpers;

class SecurityHelper
{
    /**
     * Mask email address for logging purposes.
     * Example: john.doe@example.com => j***@e***.com
     *
     * @param string|null $email
     * @return string
     */
    public static function maskEmail(?string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        // Mask local part (keep first character)
        $maskedLocal = strlen($local) > 1
            ? substr($local, 0, 1) . str_repeat('*', min(strlen($local) - 1, 3))
            : '*';

        // Mask domain (keep first character and TLD)
        $domainParts = explode('.', $domain);
        if (count($domainParts) > 1) {
            $maskedDomain = substr($domainParts[0], 0, 1) . str_repeat('*', min(strlen($domainParts[0]) - 1, 3));
            $maskedDomain .= '.' . end($domainParts);
        } else {
            $maskedDomain = substr($domain, 0, 1) . str_repeat('*', min(strlen($domain) - 1, 3));
        }

        return $maskedLocal . '@' . $maskedDomain;
    }

    /**
     * Mask IP address for logging (keep first 2 octets).
     * Example: 192.168.1.100 => 192.168.*.*
     *
     * @param string|null $ip
     * @return string
     */
    public static function maskIp(?string $ip): string
    {
        if (empty($ip)) {
            return '***';
        }

        // IPv4
        if (str_contains($ip, '.')) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0] . '.' . $parts[1] . '.*.*';
            }
        }

        // IPv6 (keep first 2 segments)
        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            if (count($parts) >= 2) {
                return $parts[0] . ':' . $parts[1] . ':***';
            }
        }

        return '***';
    }

    /**
     * Hash sensitive data for logging (one-way hash).
     *
     * @param string|null $data
     * @return string
     */
    public static function hashForLogging(?string $data): string
    {
        if (empty($data)) {
            return '***';
        }

        return substr(hash('sha256', $data), 0, 16) . '...';
    }
}
