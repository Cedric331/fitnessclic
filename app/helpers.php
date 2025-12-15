<?php

if (! function_exists('formatDuration')) {
    /**
     * Format a duration string to a readable format.
     *
     * @param  string|null  $duration
     * @return string
     */
    function formatDuration($duration)
    {
        if (empty($duration) || $duration === '-') {
            return '-';
        }
        if (strpos($duration, 'minute') !== false || strpos($duration, 'seconde') !== false) {
            return $duration;
        }

        $totalSeconds = 0;
        if (preg_match('/(\d+)\s*min/i', $duration, $m)) {
            $totalSeconds += intval($m[1]) * 60;
        }
        if (preg_match('/(\d+)\s*s/i', $duration, $m)) {
            $totalSeconds += intval($m[1]);
        }
        if ($totalSeconds === 0 && preg_match('/^(\d+)$/', $duration, $m)) {
            $totalSeconds = intval($m[1]);
        }
        if ($totalSeconds === 0) {
            return $duration;
        }

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;
        $result = '';
        if ($minutes > 0) {
            $result .= $minutes.' minute'.($minutes > 1 ? 's' : '');
        }
        if ($seconds > 0) {
            if ($minutes > 0) {
                $result .= ' ';
            }
            $result .= $seconds.' seconde'.($seconds > 1 ? 's' : '');
        }

        return $result ?: $duration;
    }
}

if (! function_exists('formatRestTime')) {
    /**
     * Format a rest time string to a readable format.
     *
     * @param  string|null  $restTime
     * @return string
     */
    function formatRestTime($restTime)
    {
        if (empty($restTime) || $restTime === '-') {
            return '-';
        }
        if (strpos($restTime, 'seconde') !== false || strpos($restTime, 'minute') !== false) {
            return $restTime;
        }
        if (preg_match('/^(\d+)$/', $restTime, $m)) {
            $seconds = intval($m[1]);
            return $seconds.' seconde'.($seconds > 1 ? 's' : '');
        }

        return $restTime;
    }
}

if (! function_exists('extractRestSeconds')) {
    /**
     * Extract rest time in seconds from a formatted string.
     *
     * @param  string|null  $restTime
     * @return int|string
     */
    function extractRestSeconds($restTime)
    {
        if (empty($restTime) || $restTime === '-') {
            return '-';
        }
        // Extraire le nombre de secondes depuis le format "X seconde(s)" ou "X secondes"
        if (preg_match('/(\d+)\s*seconde/i', $restTime, $m)) {
            return intval($m[1]);
        }
        // Si c'est juste un nombre, le retourner
        if (preg_match('/^(\d+)$/', $restTime, $m)) {
            return intval($m[1]);
        }
        // Si c'est au format "X minute(s) Y seconde(s)", convertir en secondes
        $totalSeconds = 0;
        if (preg_match('/(\d+)\s*minute/i', $restTime, $m)) {
            $totalSeconds += intval($m[1]) * 60;
        }
        if (preg_match('/(\d+)\s*seconde/i', $restTime, $m)) {
            $totalSeconds += intval($m[1]);
        }

        return $totalSeconds > 0 ? $totalSeconds : '-';
    }
}

if (! function_exists('extractDurationSeconds')) {
    /**
     * Extract duration in seconds from a formatted string.
     *
     * @param  string|null  $duration
     * @return int|string
     */
    function extractDurationSeconds($duration)
    {
        if (empty($duration) || $duration === '-') {
            return '-';
        }
        // Si c'est déjà au format "X seconde(s)" ou "X secondes", extraire le nombre
        if (preg_match('/(\d+)\s*seconde/i', $duration, $m)) {
            return intval($m[1]);
        }
        // Si c'est juste un nombre, le retourner
        if (preg_match('/^(\d+)$/', $duration, $m)) {
            return intval($m[1]);
        }
        // Si c'est au format "X minute(s) Y seconde(s)", convertir en secondes
        $totalSeconds = 0;
        if (preg_match('/(\d+)\s*minute/i', $duration, $m)) {
            $totalSeconds += intval($m[1]) * 60;
        }
        if (preg_match('/(\d+)\s*seconde/i', $duration, $m)) {
            $totalSeconds += intval($m[1]);
        }
        // Si c'est au format "X min" ou "X s", convertir
        if (preg_match('/(\d+)\s*min/i', $duration, $m)) {
            $totalSeconds += intval($m[1]) * 60;
        }
        if (preg_match('/(\d+)\s*s/i', $duration, $m)) {
            $totalSeconds += intval($m[1]);
        }

        return $totalSeconds > 0 ? $totalSeconds : '-';
    }
}

