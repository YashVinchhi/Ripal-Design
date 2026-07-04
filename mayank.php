<?php
// Secure session setup for Admin Dashboard passcode authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
define('ADMIN_PASSCODE', 'ripal2026'); // Passcode to view scan logs
$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/access_scans.log';

// Map card owners to descriptive titles
$card_owners = [
    'yash' => 'Yash (Me)',
    'me' => 'Yash (Me)',
    'mayank' => 'Mayank',
    'hemendra' => 'Hemendra (Father)',
    'father' => 'Hemendra (Father)',
    'dhaval' => 'Dhaval (Cousin Brother)',
    'cousin' => 'Dhaval (Cousin Brother)',
    'extra' => 'Extra Card'
];

// 1. Handle API actions (get_logs, clear_logs)
$action = $_GET['action'] ?? null;
$passcode = $_GET['passcode'] ?? null;

if ($action) {
    if ($passcode !== ADMIN_PASSCODE) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Unauthorized passcode'], JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    if ($action === 'get_logs') {
        $logs = [];
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $data = json_decode($line, true);
                    if ($data) {
                        $logs[] = $data;
                    }
                }
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'logs' => array_reverse($logs)], JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    if ($action === 'clear_logs') {
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Logs cleared successfully'], JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// 2. Parse scan logging parameters from URL
// Check for direct param keys: ?nfc=yash or ?qr=mayank
$nfc_param = $_GET['nfc'] ?? null;
$qr_param = $_GET['qr'] ?? null;

// Also check for card + medium parameters: ?card=yash&m=nfc
$card_param = $_GET['card'] ?? $_GET['person'] ?? $_GET['p'] ?? null;
$medium_param = $_GET['medium'] ?? $_GET['m'] ?? null;

$raw_person = null;
$medium = 'QR'; // Default fallback

if ($nfc_param !== null) {
    $raw_person = $nfc_param;
    $medium = 'NFC';
} elseif ($qr_param !== null) {
    $raw_person = $qr_param;
    $medium = 'QR';
} elseif ($card_param !== null) {
    $raw_person = $card_param;
    if ($medium_param !== null) {
        $medium = (strcasecmp($medium_param, 'nfc') === 0) ? 'NFC' : 'QR';
    }
}

// If a scan was detected, log it securely
$scanned_person = null;
$scanned_medium = null;

if ($raw_person !== null) {
    $person_key = strtolower(trim($raw_person));
    $person_name = $card_owners[$person_key] ?? ucfirst($raw_person);
    $scanned_person = $person_name;
    $scanned_medium = $medium;
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $log_entry = json_encode([
        'timestamp' => $timestamp,
        'ip' => $ip,
        'medium' => $scanned_medium,
        'owner_key' => $person_key,
        'owner_name' => $scanned_person,
        'ua' => $ua
    ], JSON_UNESCAPED_SLASHES) . "\n";
    
    // Write entry. Set strict permissions if the file is new.
    $is_new = !file_exists($log_file);
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    if ($is_new && file_exists($log_file)) {
        chmod($log_file, 0600); // Only readable/writable by owner
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mayank Vinchhi | Lead Designer | Ripal Design Studio</title>
    <meta name="description" content="Portfolio of Mayank Vinchhi, Creative Lead & Partner at Ripal Design Studio. Crafting aesthetic, functional architecture and interior designs in Gujarat.">
    
    <!-- Custom Google Fonts matching ripaldesign.studio -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600;6..72,700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for sleek social and layout icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    
    <style>
        :root {
            /* Light theme variables fetched from ripaldesign.studio */
            --bg: #f6f3ee;
            --paper: #fffdf8;
            --cream-50: #fffefa;
            --cream-100: #f8f3ea;
            --cream-200: #eee7dc;
            --cream-300: #ddd3c6;
            --ink: #2d2d2d;
            --muted: #706b64;
            --line: #e3ddd3;
            --line-strong: #cfc6ba;
            --brand: #94180c; /* Terracotta/brick-red signature brand color */
            --brand-hover: #731209;
            --shadow: 0 16px 42px rgba(45, 45, 45, 0.04);
            
            --display-font: "Newsreader", Georgia, serif;
            --body-font: "Instrument Sans", system-ui, -apple-system, Segoe UI, sans-serif;
            --mono-font: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            
            --radius: 0px; /* Sharp UI radius from website specifications */
            --transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                /* Dark theme immersive variables matching ripaldesign.studio */
                --bg: #0c0b0a;
                --paper: #121110;
                --cream-50: #161514;
                --cream-100: #1a1918;
                --cream-200: #222120;
                --cream-300: #2d2b29;
                --ink: #fdf7ef;
                --muted: #a59f95;
                --line: rgba(255, 255, 255, 0.08);
                --line-strong: rgba(255, 255, 255, 0.16);
                --brand: #b12718;
                --brand-hover: #d23220;
                --shadow: 0 16px 42px rgba(0, 0, 0, 0.4);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--body-font);
            background: 
                linear-gradient(90deg, rgba(148, 24, 12, 0.015) 1px, transparent 1px),
                linear-gradient(180deg, var(--cream-50) 0%, var(--bg) 50%, var(--cream-100) 100%),
                var(--bg);
            background-size: 10vw 100%;
            color: var(--ink);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            padding: 0;
            overflow-x: hidden;
        }

        /* Skip link */
        .skip-link {
            position: fixed;
            top: 1rem;
            left: 1rem;
            background: var(--ink);
            color: var(--bg);
            padding: 0.5rem 1rem;
            z-index: 1000;
            text-decoration: none;
            transform: translateY(-150%);
            transition: transform 0.2s;
            font-weight: 600;
        }
        .skip-link:focus {
            transform: translateY(0);
        }

        /* Container */
        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 0 1.5rem 4rem 1.5rem;
        }

        /* Site Header */
        .site-header {
            width: 100%;
            border-bottom: 1px solid var(--line);
            margin-bottom: 3.5rem;
            padding: 1.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--ink);
            text-decoration: none;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: -0.01em;
            transition: var(--transition);
        }
        .brand:hover {
            opacity: 0.85;
        }
        .brand svg {
            stroke: var(--ink);
        }
        .brand-text {
            font-family: var(--body-font);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.15em;
            font-weight: 700;
        }

        /* Section Headings */
        section {
            margin-bottom: 4rem;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
        }
        
        .section-title {
            font-family: var(--display-font);
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
        }
        
        .section-line {
            flex-grow: 1;
            height: 1px;
            background-color: var(--line);
        }

        /* Hero styling */
        .hero {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 4rem;
            position: relative;
        }
        .hero-eyebrow {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--brand);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .hero-eyebrow::after {
            content: '';
            width: 32px;
            height: 1px;
            background-color: var(--brand);
            display: inline-block;
        }
        .hero h1 {
            font-family: var(--display-font);
            font-size: clamp(2.4rem, 6vw, 3.5rem);
            font-weight: 500;
            line-height: 1.05;
            letter-spacing: -0.01em;
        }
        .hero p {
            color: var(--muted);
            font-size: clamp(1rem, 2vw, 1.15rem);
            max-width: 92%;
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        /* Social Icons */
        .socials {
            display: flex;
            gap: 1.25rem;
            margin: 0.5rem 0;
            flex-wrap: wrap;
        }
        .social-link {
            color: var(--muted);
            font-size: 1.2rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--paper);
            text-decoration: none;
        }
        .social-link:hover {
            color: var(--brand);
            border-color: var(--brand);
            background: var(--cream-100);
            transform: translateY(-2px);
        }

        /* Buttons */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: var(--brand);
            color: #fffdf8;
            padding: 0.85rem 1.75rem;
            border: 1px solid var(--brand);
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 750;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            transition: var(--transition);
            width: fit-content;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: var(--brand-hover);
            border-color: var(--brand-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        .btn-primary svg {
            width: 16px;
            height: 16px;
            margin-right: 0.65rem;
            fill: currentColor;
        }

        /* Story & Milestones styling */
        .story-text {
            color: var(--muted);
            font-size: 1.02rem;
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        .milestone-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            margin-top: 1.75rem;
        }
        @media (min-width: 600px) {
            .milestone-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        .milestone-card {
            border: 1px solid var(--line);
            background: var(--paper);
            padding: 1.25rem;
            transition: var(--transition);
        }
        .milestone-card:hover {
            border-color: var(--brand);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        .milestone-year {
            font-family: var(--display-font);
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--brand);
            margin-bottom: 0.35rem;
        }
        .milestone-title {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ink);
            margin-bottom: 0.5rem;
        }
        .milestone-desc {
            font-size: 0.82rem;
            color: var(--muted);
            line-height: 1.5;
        }
        .philosophy-callout {
            border-left: 3px solid var(--brand);
            padding-left: 1.5rem;
            margin: 2.25rem 0;
            font-family: var(--display-font);
            font-size: 1.2rem;
            font-style: italic;
            color: var(--ink);
            line-height: 1.6;
        }

        /* Testimonials Card & Rotator */
        .testimonial-card {
            border: 1px solid var(--line);
            background: var(--cream-100);
            padding: 1.75rem;
            position: relative;
        }
        .testimonial-card::after {
            content: '“';
            position: absolute;
            top: -0.2rem;
            right: 1.5rem;
            font-family: var(--display-font);
            font-size: 5rem;
            color: var(--cream-300);
            line-height: 1;
            pointer-events: none;
            opacity: 0.6;
        }
        .testimonial-quote {
            font-family: var(--display-font);
            font-size: 1.1rem;
            font-style: italic;
            color: var(--ink);
            line-height: 1.5;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        .testimonial-author {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* Contact Details list */
        .contact-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        .contact-item {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border: 1px solid var(--line);
            background: var(--paper);
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }
        .contact-item:hover {
            border-color: var(--brand);
            background: var(--cream-50);
            transform: translateX(3px);
        }
        .contact-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cream-100);
            color: var(--brand);
            font-size: 1.05rem;
            margin-right: 1.25rem;
            transition: var(--transition);
            border: 1px solid var(--line);
        }
        .contact-item:hover .contact-icon {
            background: var(--brand);
            color: #fffdf8;
            border-color: var(--brand);
        }
        .contact-details {
            flex-grow: 1;
        }
        .contact-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.15rem;
        }
        .contact-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--ink);
        }

        /* Scan Welcome Toast Styles */
        .scan-toast {
            position: fixed;
            top: 2rem;
            left: 50%;
            transform: translate(-50%, -20px);
            background: rgba(255, 253, 248, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 24, 12, 0.2);
            box-shadow: 0 20px 40px rgba(148, 24, 12, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
        }
        @media (prefers-color-scheme: dark) {
            .scan-toast {
                background: rgba(18, 17, 16, 0.92);
                border-color: rgba(177, 39, 24, 0.3);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            }
        }
        .scan-toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translate(-50%, 0);
        }
        .scan-toast-icon {
            width: 36px;
            height: 36px;
            background: var(--brand);
            color: #fffdf8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(148, 24, 12, 0.2);
        }
        .scan-toast-content {
            display: flex;
            flex-direction: column;
        }
        .scan-toast-title {
            font-family: var(--display-font);
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .scan-toast-body {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 0.15rem;
            white-space: nowrap;
        }
        .scan-toast-close {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 1rem;
            transition: var(--transition);
        }
        .scan-toast-close:hover {
            color: var(--brand);
        }

        /* Admin Dashboard Styles */
        .admin-link {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.65rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            text-decoration: none;
            border-bottom: 1px dashed var(--line);
            transition: var(--transition);
            cursor: pointer;
        }
        .admin-link:hover {
            color: var(--brand);
            border-color: var(--brand);
        }

        .dashboard-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(246, 243, 238, 0.98);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            z-index: 10000;
            display: none;
            overflow-y: auto;
            padding: 3rem 1.5rem;
        }
        @media (prefers-color-scheme: dark) {
            .dashboard-overlay {
                background: rgba(12, 11, 10, 0.98);
            }
        }
        .dashboard-overlay.show {
            display: block;
        }
        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--line-strong);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .dashboard-title {
            font-family: var(--display-font);
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--brand);
        }
        .dashboard-close {
            background: none;
            border: none;
            color: var(--ink);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .dashboard-close:hover {
            color: var(--brand);
            transform: scale(1.1);
        }
        
        /* Passcode Form screen */
        .passcode-screen {
            max-width: 360px;
            margin: 6rem auto 0 auto;
            text-align: center;
            border: 1px solid var(--line);
            background: var(--paper);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
        }
        .passcode-screen h3 {
            font-family: var(--display-font);
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--ink);
        }
        .passcode-input {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            border: 1px solid var(--line-strong);
            background: var(--bg);
            color: var(--ink);
            text-align: center;
            letter-spacing: 0.2em;
            margin-bottom: 1.25rem;
            font-family: var(--mono-font);
        }
        .passcode-input:focus {
            border-color: var(--brand);
            outline: none;
        }
        .passcode-error {
            color: var(--brand);
            font-size: 0.8rem;
            margin-bottom: 1rem;
            display: none;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }
        @media (min-width: 600px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        .stat-card {
            border: 1px solid var(--line);
            background: var(--paper);
            padding: 1.5rem;
            position: relative;
        }
        .stat-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-family: var(--display-font);
            font-size: 2rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
        }
        .stat-detail {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 0.25rem;
        }

        /* Logs Table */
        .logs-section-title {
            font-family: var(--display-font);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logs-actions {
            display: flex;
            gap: 0.75rem;
        }
        .btn-secondary {
            background: var(--paper);
            color: var(--ink);
            border: 1px solid var(--line-strong);
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-secondary:hover {
            border-color: var(--brand);
            color: var(--brand);
            background: var(--cream-50);
        }
        .btn-secondary.danger:hover {
            border-color: var(--brand);
            background: var(--brand);
            color: #fffdf8;
        }
        
        .logs-wrapper {
            border: 1px solid var(--line);
            background: var(--paper);
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            text-align: left;
        }
        .logs-table th {
            background: var(--cream-100);
            padding: 0.75rem 1rem;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            color: var(--muted);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .logs-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--line);
            color: var(--ink);
            vertical-align: middle;
        }
        .logs-table tr:last-child td {
            border-bottom: none;
        }
        .logs-table tr:hover td {
            background: var(--cream-50);
        }
        .medium-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.15rem 0.5rem;
            background: var(--cream-100);
            color: var(--muted);
            border: 1px solid var(--line);
        }
        .medium-badge.nfc {
            background: rgba(148, 24, 12, 0.06);
            color: var(--brand);
            border-color: rgba(148, 24, 12, 0.15);
        }
        .ua-text {
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            cursor: help;
            color: var(--muted);
        }
        .no-data {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
            font-style: italic;
        }

        /* Footer styling */
        footer {
            margin-top: 5rem;
            border-top: 1px dashed var(--line-strong);
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
    </style>
</head>
<body>

    <a class="skip-link" href="#main">Skip to content</a>

    <div class="container">
        <!-- Site Header Matching Studio Website -->
        <header class="site-header">
            <a class="brand" href="https://ripaldesign.studio/index.php" target="_blank" aria-label="Ripal Design home">
                <!-- Clean Architectural SVG Grid Logo -->
                <svg width="30" height="30" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 28V4h24v24H4z" />
                    <path d="M12 4v24M4 12h24M20 4v24M4 20h24" stroke-dasharray="1 2" stroke-width="1"/>
                    <path d="M4 28l24-24M4 4l24 24" />
                    <circle cx="16" cy="16" r="4" fill="var(--brand)" stroke="none"/>
                </svg>
                <span class="brand-text">Ripal Design</span>
            </a>
            
            <a href="https://ripaldesign.studio/contact_us.php" target="_blank" class="brand-text" style="text-decoration:none; font-size:0.72rem; border-bottom:1px solid var(--line);">Start a Project</a>
        </header>

        <main id="main">
            <!-- Hero Section -->
            <section class="hero">
                <div class="hero-eyebrow">Est. 2017 / Rajkot</div>
                <h1>Mayank Vinchhi</h1>
                <p>Creative Designer & Execution Lead at Ripal Design Studio. Crafting clean, minimalist, and structurally sound architectural blueprints and high-end residential interiors.</p>
                
                <div class="socials">
                    <a href="https://www.behance.net/mayankvinchhi" class="social-link" target="_blank" aria-label="Behance">
                        <i class="fa-brands fa-behance"></i>
                    </a>
                    <a href="https://www.instagram.com/mynk_jpg/" class="social-link" target="_blank" aria-label="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="mailto:mayank1111vinchhi@gmail.com" class="social-link" aria-label="Email">
                        <i class="fa-regular fa-envelope"></i>
                    </a>
                    <a href="https://wa.me/918530324404" class="social-link" target="_blank" aria-label="WhatsApp Direct">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                </div>

                <a href="https://wa.me/918530324404" class="btn-primary" target="_blank">
                    <i class="fa-brands fa-whatsapp"></i> Let's Talk
                </a>
            </section>

            <!-- Story & Milestones Section (Fetched from ripaldesign.studio) -->
            <section>
                <div class="section-header">
                    <h2 class="section-title">My Story</h2>
                    <div class="section-line"></div>
                </div>
                
                <p class="story-text">
                    As the Execution Lead and Partner at Ripal Design Studio, I oversee on-site project delivery, vendor coordination, and schedule integrity. Working in tandem with Design Director Dhaval Vinchhi, we transform design concepts and architectural blueprints into timeless built realities across Rajkot and greater Gujarat.
                </p>

                <div class="philosophy-callout">
                    "Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail."
                </div>

                <div class="milestone-grid">
                    <div class="milestone-card">
                        <div class="milestone-year">2017</div>
                        <div class="milestone-title">Inception</div>
                        <div class="milestone-desc">Firm established in Rajkot with a design-build model, bridging the gap between concept and execution.</div>
                    </div>
                    <div class="milestone-card">
                        <div class="milestone-year">Scale</div>
                        <div class="milestone-title">Expansion</div>
                        <div class="milestone-desc">Expanded into municipal and public projects and scaled the core execution team for large commercial builds.</div>
                    </div>
                    <div class="milestone-card">
                        <div class="milestone-year">Future</div>
                        <div class="milestone-title">Consultancy</div>
                        <div class="milestone-desc">Aiming for global consultancy status and integrating sustainable building tech in every design.</div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section (Fetched from website) -->
            <section>
                <div class="section-header">
                    <h2 class="section-title">Client Perspectives</h2>
                    <div class="section-line"></div>
                </div>
                
                <div class="testimonial-card">
                    <p class="testimonial-quote" id="testimonialQuote">"The surgical precision of their design language transformed our site into a masterpiece of modern architecture."</p>
                    <div class="testimonial-author" id="testimonialAuthor">Amitbhai Patel, Chairman, Rajkot Realty Group</div>
                </div>
            </section>

            <!-- Contact Section -->
            <section>
                <div class="section-header">
                    <h2 class="section-title">Get in Touch</h2>
                    <div class="section-line"></div>
                </div>
                
                <div class="contact-list">
                    <a href="mailto:mayank1111vinchhi@gmail.com" class="contact-item">
                        <div class="contact-icon">
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <div class="contact-label">Email</div>
                            <div class="contact-value">mayank1111vinchhi@gmail.com</div>
                        </div>
                    </a>

                    <a href="tel:+918530324404" class="contact-item">
                        <div class="contact-icon">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <div class="contact-label">Phone Call</div>
                            <div class="contact-value">+91 85303 24404</div>
                        </div>
                    </a>

                    <a href="https://wa.me/918530324404" class="contact-item" target="_blank">
                        <div class="contact-icon">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div class="contact-details">
                            <div class="contact-label">WhatsApp</div>
                            <div class="contact-value">+91 85303 24404</div>
                        </div>
                    </a>
                </div>
            </section>
        </main>

        <!-- Footer styled cleanly -->
        <footer>
            <div style="font-family: var(--body-font); font-size: 0.72rem; color: var(--muted); text-align: center; letter-spacing: 0.08em; text-transform: uppercase;">
                &copy; 2026 Ripal Design. All rights reserved.
                <br>
                <span class="admin-link" onclick="openAdminDashboard()">Admin Scan Dashboard</span>
            </div>
        </footer>
    </div>

    <!-- 1. Scan Welcome Toast Notification -->
    <?php if ($scanned_person): ?>
    <div id="scanToast" class="scan-toast">
        <div class="scan-toast-icon">
            <i class="fa-solid <?php echo $scanned_medium === 'NFC' ? 'fa-wifi' : 'fa-qrcode'; ?>"></i>
        </div>
        <div class="scan-toast-content">
            <div class="scan-toast-title">Welcome, <?php echo htmlspecialchars($scanned_person, ENT_QUOTES, 'UTF-8'); ?>!</div>
            <div class="scan-toast-body">Card accessed via <?php echo htmlspecialchars($scanned_medium, ENT_QUOTES, 'UTF-8'); ?> successfully.</div>
        </div>
        <button class="scan-toast-close" onclick="closeScanToast()" aria-label="Close message">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- 2. Admin Dashboard Modal Overlay -->
    <div id="adminDashboard" class="dashboard-overlay">
        <div class="dashboard-container">
            
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <i class="fa-solid fa-chart-line"></i> Scan Analytics Dashboard
                </div>
                <button class="dashboard-close" onclick="closeAdminDashboard()" aria-label="Close Dashboard">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- passcode Screen (shown if not authenticated) -->
            <div id="passcodeScreen" class="passcode-screen">
                <i class="fa-solid fa-lock" style="font-size: 2rem; color: var(--brand); margin-bottom: 1.5rem;"></i>
                <h3>Enter Admin Passcode</h3>
                <p style="font-size: 0.82rem; color: var(--muted); margin-bottom: 1.5rem;">Verify access to view visitor tracking logs.</p>
                <input type="password" id="passcodeInput" class="passcode-input" placeholder="••••••••" onkeydown="if(event.key==='Enter') submitPasscode()">
                <div id="passcodeError" class="passcode-error">Incorrect passcode. Please try again.</div>
                <button class="btn-primary" onclick="submitPasscode()" style="width: 100%;">Access Dashboard</button>
            </div>

            <!-- Main Dashboard Panel (shown if authenticated) -->
            <div id="dashboardPanel" style="display: none;">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Scan Events</div>
                        <div class="stat-value" id="statTotalScans">0</div>
                        <div class="stat-detail">Across all NFC & QR cards</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Access Medium Split</div>
                        <div class="stat-value" id="statMediumSplit" style="font-size: 1.4rem; margin-top: 0.25rem;">NFC: 0 | QR: 0</div>
                        <div class="stat-detail" id="statMediumPct">NFC: 0% | QR: 0%</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Most Active Card</div>
                        <div class="stat-value" id="statTopCard" style="font-size: 1.25rem; font-family: var(--body-font); font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 0.35rem;">-</div>
                        <div class="stat-detail" id="statTopCardCount">0 scans registered</div>
                    </div>
                </div>

                <!-- Breakdown per Person -->
                <div style="margin-bottom: 2.5rem;">
                    <h3 class="logs-section-title">Card Breakdown</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem;" id="breakdownGrid">
                        <!-- Dynamic breakdown cards will go here -->
                    </div>
                </div>

                <!-- Detailed Logs Table -->
                <div>
                    <div class="logs-section-title">
                        <span>Recent Visits Feed</span>
                        <div class="logs-actions">
                            <button class="btn-secondary" onclick="fetchLogs()"><i class="fa-solid fa-rotate"></i> Refresh</button>
                            <button class="btn-secondary" onclick="exportLogsCSV()"><i class="fa-solid fa-download"></i> CSV</button>
                            <button class="btn-secondary danger" onclick="clearLogs()"><i class="fa-solid fa-trash-can"></i> Clear History</button>
                        </div>
                    </div>
                    
                    <div class="logs-wrapper">
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th style="width: 160px;">Date & Time</th>
                                    <th style="width: 140px;">Visiting Card</th>
                                    <th style="width: 90px;">Medium</th>
                                    <th style="width: 110px;">IP Address</th>
                                    <th>Device / Browser</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <!-- Logs will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        // Typewriter script removed

        // Testimonial rotation matching ripaldesign.studio rotation speed
        const testimonials = [
            {
                quote: "The surgical precision of their design language transformed our site into a masterpiece of modern architecture.",
                author: "Amitbhai Patel, Chairman, Rajkot Realty Group"
            },
            {
                quote: "They pushed the boundaries of what we thought was possible, creating a space that feels both Intimate and Grand.",
                author: "Anilbhai Sharma, Founder, Khambhalia Arts"
            },
            {
                quote: "Deeply committed to sustainability without compromising on aesthetic excellence. Truly leaders in the new era.",
                author: "Sureshbhai, Director, Regional Urban Planning"
            }
        ];
        
        let testimonialIndex = 0;
        const quoteElement = document.getElementById("testimonialQuote");
        const authorElement = document.getElementById("testimonialAuthor");
        
        setInterval(() => {
            testimonialIndex = (testimonialIndex + 1) % testimonials.length;
            
            // Subtle transition fade out
            quoteElement.style.opacity = 0;
            authorElement.style.opacity = 0;
            quoteElement.style.transform = "translateY(5px)";
            quoteElement.style.transition = "opacity 0.3s ease, transform 0.3s ease";
            authorElement.style.transition = "opacity 0.3s ease, transform 0.3s ease";
            
            setTimeout(() => {
                quoteElement.textContent = `"${testimonials[testimonialIndex].quote}"`;
                authorElement.textContent = testimonials[testimonialIndex].author;
                
                // Fade back in
                quoteElement.style.opacity = 1;
                authorElement.style.opacity = 1;
                quoteElement.style.transform = "translateY(0)";
            }, 300);
            
        }, 4200);

        // --- Visitor Scanning & Analytics Controller ---
        
        document.addEventListener("DOMContentLoaded", () => {
            // 1. Show visitor greeting toast if triggered
            const toast = document.getElementById("scanToast");
            if (toast) {
                setTimeout(() => {
                    toast.classList.add("show");
                }, 600);
                
                // Auto hide after 8 seconds
                setTimeout(() => {
                    closeScanToast();
                }, 8600);
            }

            // 2. If URL contains ?admin=true, automatically open the dashboard
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('admin') === 'true') {
                openAdminDashboard();
                // Clean url parameters to keep it pristine
                const url = new URL(window.location.href);
                url.searchParams.delete('admin');
                window.history.replaceState({}, document.title, url.pathname);
            }
        });

        function closeScanToast() {
            const toast = document.getElementById("scanToast");
            if (toast) {
                toast.classList.remove("show");
                // Remove the URL query parameters so reloading doesn't log/trigger again
                const url = new URL(window.location.href);
                url.searchParams.delete('nfc');
                url.searchParams.delete('qr');
                url.searchParams.delete('card');
                url.searchParams.delete('person');
                url.searchParams.delete('p');
                url.searchParams.delete('medium');
                url.searchParams.delete('m');
                window.history.replaceState({}, document.title, url.pathname);
            }
        }

        // Dashboard overlay controls
        function openAdminDashboard() {
            const overlay = document.getElementById("adminDashboard");
            overlay.classList.add("show");
            document.body.style.overflow = "hidden"; // Prevent background scrolling
            
            if (sessionStorage.getItem("admin_authenticated") === "true") {
                showDashboardPanel();
            } else {
                showPasscodeScreen();
            }
        }

        function closeAdminDashboard() {
            const overlay = document.getElementById("adminDashboard");
            overlay.classList.remove("show");
            document.body.style.overflow = ""; // Re-enable background scrolling
        }

        function showPasscodeScreen() {
            document.getElementById("passcodeScreen").style.display = "block";
            document.getElementById("dashboardPanel").style.display = "none";
            document.getElementById("passcodeInput").value = "";
            document.getElementById("passcodeError").style.display = "none";
            document.getElementById("passcodeInput").focus();
        }

        function showDashboardPanel() {
            document.getElementById("passcodeScreen").style.display = "none";
            document.getElementById("dashboardPanel").style.display = "block";
            fetchLogs();
        }

        function submitPasscode() {
            const code = document.getElementById("passcodeInput").value;
            fetch(`mayank.php?action=get_logs&passcode=${encodeURIComponent(code)}`)
                .then(response => {
                    if (response.ok) {
                        sessionStorage.setItem("admin_authenticated", "true");
                        sessionStorage.setItem("admin_passcode", code);
                        showDashboardPanel();
                    } else {
                        document.getElementById("passcodeError").style.display = "block";
                    }
                })
                .catch(err => {
                    console.error("Auth error:", err);
                    document.getElementById("passcodeError").style.display = "block";
                });
        }

        function getPasscode() {
            return sessionStorage.getItem("admin_passcode") || '';
        }

        function parseUA(userAgent) {
            if (!userAgent) return 'Unknown';
            if (userAgent.includes('Android')) {
                return 'Android Mobile';
            }
            if (userAgent.includes('iPhone')) {
                return 'Apple iPhone';
            }
            if (userAgent.includes('iPad')) {
                return 'Apple iPad';
            }
            if (userAgent.includes('Windows')) {
                return 'Windows PC';
            }
            if (userAgent.includes('Macintosh')) {
                return 'macOS Device';
            }
            if (userAgent.includes('Linux')) {
                return 'Linux PC';
            }
            return 'Mobile Browser';
        }

        function fetchLogs() {
            const code = getPasscode();
            const tableBody = document.getElementById("logsTableBody");
            
            tableBody.innerHTML = `<tr><td colspan="5" class="no-data"><i class="fa-solid fa-spinner fa-spin"></i> Loading access history...</td></tr>`;
            
            fetch(`mayank.php?action=get_logs&passcode=${encodeURIComponent(code)}`)
                .then(res => {
                    if (!res.ok) throw new Error("Unauthorized");
                    return res.json();
                })
                .then(data => {
                    if (data.success && data.logs) {
                        renderDashboard(data.logs);
                    } else {
                        tableBody.innerHTML = `<tr><td colspan="5" class="no-data" style="color:var(--brand);"><i class="fa-solid fa-triangle-exclamation"></i> Error loading logs. Session expired.</td></tr>`;
                        showPasscodeScreen();
                    }
                })
                .catch(err => {
                    tableBody.innerHTML = `<tr><td colspan="5" class="no-data" style="color:var(--brand);"><i class="fa-solid fa-triangle-exclamation"></i> Unauthorized session or network error.</td></tr>`;
                });
        }

        function renderDashboard(logs) {
            const tableBody = document.getElementById("logsTableBody");
            const totalScansEl = document.getElementById("statTotalScans");
            const mediumSplitEl = document.getElementById("statMediumSplit");
            const mediumPctEl = document.getElementById("statMediumPct");
            const topCardEl = document.getElementById("statTopCard");
            const topCardCountEl = document.getElementById("statTopCardCount");
            const breakdownGrid = document.getElementById("breakdownGrid");
            
            totalScansEl.textContent = logs.length;
            
            if (logs.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="5" class="no-data"><i class="fa-solid fa-folder-open"></i> No scans registered yet. Hand out some cards!</td></tr>`;
                mediumSplitEl.textContent = "NFC: 0 | QR: 0";
                mediumPctEl.textContent = "NFC: 0% | QR: 0%";
                topCardEl.textContent = "-";
                topCardCountEl.textContent = "0 scans registered";
                breakdownGrid.innerHTML = '';
                return;
            }
            
            let nfcCount = 0;
            let qrCount = 0;
            
            const uniqueOwnersCounts = {
                'Yash (Me)': 0,
                'Mayank': 0,
                'Hemendra (Father)': 0,
                'Dhaval (Cousin Brother)': 0,
                'Extra Card': 0
            };
            
            let rowsHtml = '';
            logs.forEach(log => {
                if (log.medium === 'NFC') nfcCount++;
                else qrCount++;
                
                const name = log.owner_name || 'Unknown';
                if (name in uniqueOwnersCounts) {
                    uniqueOwnersCounts[name]++;
                } else {
                    uniqueOwnersCounts[name] = 1;
                }
                
                const dateObj = new Date(log.timestamp);
                const formattedDate = dateObj.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                const badgeClass = log.medium === 'NFC' ? 'medium-badge nfc' : 'medium-badge';
                const badgeIcon = log.medium === 'NFC' ? '<i class="fa-solid fa-wifi"></i>' : '<i class="fa-solid fa-qrcode"></i>';
                const deviceName = parseUA(log.ua);
                
                rowsHtml += `
                    <tr>
                        <td style="font-family: var(--mono-font); font-size:0.75rem;">${formattedDate}</td>
                        <td style="font-weight:700; color:var(--ink);">${name}</td>
                        <td><span class="${badgeClass}">${badgeIcon} ${log.medium}</span></td>
                        <td style="font-family: var(--mono-font); font-size:0.75rem; color:var(--muted);">${log.ip}</td>
                        <td><span class="ua-text" title="${log.ua.replace(/"/g, '&quot;')}">${deviceName}</span></td>
                    </tr>
                `;
            });
            
            tableBody.innerHTML = rowsHtml;
            
            // Render Stats
            mediumSplitEl.textContent = `NFC: ${nfcCount} | QR: ${qrCount}`;
            const nfcPct = Math.round((nfcCount / logs.length) * 100);
            const qrPct = 100 - nfcPct;
            mediumPctEl.textContent = `NFC: ${nfcPct}% | QR: ${qrPct}%`;
            
            // Find Top Card
            let maxScans = -1;
            let topCard = '-';
            for (const [owner, count] of Object.entries(uniqueOwnersCounts)) {
                if (count > maxScans) {
                    maxScans = count;
                    topCard = owner;
                }
            }
            
            if (maxScans > 0) {
                topCardEl.textContent = topCard;
                topCardCountEl.textContent = `${maxScans} scan${maxScans > 1 ? 's' : ''}`;
            } else {
                topCardEl.textContent = "-";
                topCardCountEl.textContent = "0 scans";
            }
            
            // Render Card Breakdown grid
            let breakdownHtml = '';
            for (const [owner, count] of Object.entries(uniqueOwnersCounts)) {
                const activeClass = count > 0 ? 'style="border-color:var(--brand);"' : '';
                const pct = Math.round((count / logs.length) * 100) || 0;
                breakdownHtml += `
                    <div class="stat-card" ${activeClass}>
                        <div style="font-size:0.8rem; font-weight:700; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${owner}">${owner}</div>
                        <div style="font-family:var(--display-font); font-size:1.5rem; font-weight:700; color:var(--brand); margin-top:0.25rem;">${count}</div>
                        <div style="font-size:0.65rem; color:var(--muted);">${pct}% of total</div>
                    </div>
                `;
            }
            breakdownGrid.innerHTML = breakdownHtml;
        }

        function clearLogs() {
            if (!confirm("Are you sure you want to permanently clear all scan analytics logs? This action cannot be undone.")) {
                return;
            }
            const code = getPasscode();
            fetch(`mayank.php?action=clear_logs&passcode=${encodeURIComponent(code)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        fetchLogs();
                    } else {
                        alert("Error clearing logs: session invalid.");
                        showPasscodeScreen();
                    }
                })
                .catch(err => {
                    alert("Network error clearing logs.");
                });
        }

        function exportLogsCSV() {
            const code = getPasscode();
            fetch(`mayank.php?action=get_logs&passcode=${encodeURIComponent(code)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.logs) {
                        let csvContent = "data:text/csv;charset=utf-8,";
                        csvContent += "Timestamp,Visiting Card,Medium,IP Address,User Agent\n";
                        
                        data.logs.forEach(log => {
                            const row = [
                                log.timestamp,
                                log.owner_name,
                                log.medium,
                                log.ip,
                                `"${log.ua.replace(/"/g, '""')}"`
                            ].join(",");
                            csvContent += row + "\n";
                        });
                        
                        const encodedUri = encodeURI(csvContent);
                        const link = document.createElement("a");
                        link.setAttribute("href", encodedUri);
                        link.setAttribute("download", `ripal_design_scan_analytics_${new Date().toISOString().slice(0,10)}.csv`);
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } else {
                        alert("Session expired. Please log in again.");
                        showPasscodeScreen();
                    }
                })
                .catch(err => {
                    alert("Error exporting CSV: " + err);
                });
        }
    </script>
</body>
</html>