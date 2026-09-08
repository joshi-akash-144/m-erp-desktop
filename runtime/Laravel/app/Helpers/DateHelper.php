<?php

namespace App\Helpers;

use DateTime;

class DateHelper
{
    /**
     * Format date from YYYY-MM-DD to DD-MM-YYYY
     *
     * @param string|null $date
     * @param string $format Output format, default 'd-m-Y'
     * @return string
     */
    public static function formatDate(?string $date, string $format = 'd-m-Y'): string
    {
        if (!$date) {
            return '--'; // handle null or empty date
        }

        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (\Exception $e) {
            return '--'; // invalid date
        }
    }

    /**
     * Get current financial year
     *
     * @param string|null $date
     * @return string e.g. 2025-2026
     */
    public static function financialYear(?string $date = null): string
    {
        $date = $date ? strtotime($date) : time();
        $year = (int)date('Y', $date);
        $month = (int)date('n', $date);

        if ($month < 4) {
            return ($year - 1) . '-' . $year;
        } else {
            return $year . '-' . ($year + 1);
        }
    }

    /**
     * Format datetime with time (optional)
     *
     * @param string|null $datetime
     * @param string $format
     * @return string
     */
    public static function formatDateTime(?string $datetime, string $format = 'd-m-Y H:i:s'): string
    {
        if (!$datetime) return '--';

        try {
            $dt = new DateTime($datetime);
            return $dt->format($format);
        } catch (\Exception $e) {
            return '--';
        }
    }
}
