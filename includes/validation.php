<?php
/**
 * Validation Helper Functions
 * 
 * Server-side form validation utilities.
 * Provides common validation rules and error handling.
 * 
 * @package RipalDesign
 * @subpackage Validation
 */

if (!function_exists('validate_required')) {
    /**
     * Validate required field
     * 
     * @param mixed $value Field value
     * @return bool True if valid
     */
    function validate_required($value) {
        if (is_string($value)) {
            return trim($value) !== '';
        }
        return !empty($value);
    }
}

if (!function_exists('validate_email')) {
    /**
     * Validate email address
     * 
     * @param string $email Email address
     * @return bool True if valid
     */
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validate_min_length')) {
    /**
     * Validate minimum string length
     * 
     * @param string $value String to validate
     * @param int $min Minimum length
     * @return bool True if valid
     */
    function validate_min_length($value, $min) {
        return mb_strlen($value) >= $min;
    }
}

if (!function_exists('validate_max_length')) {
    /**
     * Validate maximum string length
     * 
     * @param string $value String to validate
     * @param int $max Maximum length
     * @return bool True if valid
     */
    function validate_max_length($value, $max) {
        return mb_strlen($value) <= $max;
    }
}

if (!function_exists('validate_numeric')) {
    /**
     * Validate numeric value
     * 
     * @param mixed $value Value to validate
     * @return bool True if valid
     */
    function validate_numeric($value) {
        return is_numeric($value);
    }
}

if (!function_exists('validate_alpha')) {
    /**
     * Validate alphabetic characters only
     * 
     * @param string $value String to validate
     * @return bool True if valid
     */
    function validate_alpha($value) {
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }
}

if (!function_exists('validate_alphanumeric')) {
    /**
     * Validate alphanumeric characters only
     * 
     * @param string $value String to validate
     * @return bool True if valid
     */
    function validate_alphanumeric($value) {
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }
}

if (!function_exists('validate_phone')) {
    /**
     * Validate phone number (basic validation)
     * 
     * @param string $phone Phone number
     * @return bool True if valid
     */
    function validate_phone($phone) {
        // Remove common separators
        $cleaned = preg_replace('/[\s\-\(\)]+/', '', $phone);
        // Check if it's 10-15 digits
        return preg_match('/^[0-9]{10,15}$/', $cleaned) === 1;
    }
}

if (!function_exists('validate_url')) {
    /**
     * Validate URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid
     */
    function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('validate_date')) {
    /**
     * Validate date format (Y-m-d)
     * 
     * @param string $date Date string
     * @return bool True if valid
     */
    function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

if (!function_exists('validate_match')) {
    /**
     * Validate that two values match
     * 
     * @param mixed $value1 First value
     * @param mixed $value2 Second value
     * @return bool True if they match
     */
    function validate_match($value1, $value2) {
        return $value1 === $value2;
    }
}

if (!function_exists('validate_in')) {
    /**
     * Validate that value is in array of allowed values
     * 
     * @param mixed $value Value to check
     * @param array $allowed Allowed values
     * @return bool True if valid
     */
    function validate_in($value, $allowed) {
        return in_array($value, $allowed, true);
    }
}

if (!function_exists('validate_password_strength')) {
    /**
     * Validate password strength
     * Requirements: At least 8 chars, 1 uppercase, 1 lowercase, 1 number
     * 
     * @param string $password Password to validate
     * @return bool True if valid
     */
    function validate_password_strength($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        // Must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Must contain at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
}

if (!function_exists('validate_data')) {
    /**
     * Validate data array against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Errors array (empty if valid)
     */
    function validate_data($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            
            foreach ($ruleList as $rule) {
                // Parse rule and parameters
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $params) = explode(':', $rule, 2);
                    $params = explode(',', $params);
                } else {
                    $ruleName = $rule;
                    $params = [];
                }
                
                // Apply validation rule
                $isValid = true;
                $errorMessage = '';
                
                switch ($ruleName) {
                    case 'required':
                        $isValid = validate_required($value);
                        $errorMessage = ucfirst($field) . ' is required.';
                        break;
                        
                    case 'email':
                        if (!empty($value)) {
                            $isValid = validate_email($value);
                            $errorMessage = ucfirst($field) . ' must be a valid email address.';
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value)) {
                            $isValid = validate_min_length($value, (int)$params[0]);
                            $errorMessage = ucfirst($field) . ' must be at least ' . $params[0] . ' characters.';
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value)) {
                            $isValid = validate_max_length($value, (int)$params[0]);
                            $errorMessage = ucfirst($field) . ' must not exceed ' . $params[0] . ' characters.';
                        }
                        break;
                        
                    case 'numeric':
                        if (!empty($value)) {
                            $isValid = validate_numeric($value);
                            $errorMessage = ucfirst($field) . ' must be a number.';
                        }
                        break;
                        
                    case 'alpha':
                        if (!empty($value)) {
                            $isValid = validate_alpha($value);
                            $errorMessage = ucfirst($field) . ' must contain only letters.';
                        }
                        break;
                        
                    case 'alphanumeric':
                        if (!empty($value)) {
                            $isValid = validate_alphanumeric($value);
                            $errorMessage = ucfirst($field) . ' must contain only letters and numbers.';
                        }
                        break;
                        
                    case 'phone':
                        if (!empty($value)) {
                            $isValid = validate_phone($value);
                            $errorMessage = ucfirst($field) . ' must be a valid phone number.';
                        }
                        break;
                        
                    case 'url':
                        if (!empty($value)) {
                            $isValid = validate_url($value);
                            $errorMessage = ucfirst($field) . ' must be a valid URL.';
                        }
                        break;
                        
                    case 'date':
                        if (!empty($value)) {
                            $isValid = validate_date($value);
                            $errorMessage = ucfirst($field) . ' must be a valid date (Y-m-d).';
                        }
                        break;
                        
                    case 'match':
                        if (!empty($value)) {
                            $matchField = $params[0] ?? '';
                            $isValid = validate_match($value, $data[$matchField] ?? null);
                            $errorMessage = ucfirst($field) . ' must match ' . $matchField . '.';
                        }
                        break;
                        
                    case 'in':
                        if (!empty($value)) {
                            $isValid = validate_in($value, $params);
                            $errorMessage = ucfirst($field) . ' must be one of: ' . implode(', ', $params) . '.';
                        }
                        break;
                        
                    case 'strong_password':
                        if (!empty($value)) {
                            $isValid = validate_password_strength($value);
                            $errorMessage = ucfirst($field) . ' must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.';
                        }
                        break;
                }
                
                // Store error if validation failed
                if (!$isValid) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = $errorMessage;
                    }
                    break; // Stop validating this field on first error
                }
            }
        }
        
        return $errors;
    }
}

if (!function_exists('store_validation_errors')) {
    /**
     * Store validation errors in session
     * 
     * @param array $errors Errors array
     * @return void
     */
    function store_validation_errors($errors) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['form_errors'] = $errors;
    }
}

if (!function_exists('store_old_input')) {
    /**
     * Store old input in session for re-population
     * 
     * @param array $data Input data
     * @return void
     */
    function store_old_input($data) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Don't store passwords or sensitive data
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'csrf_token'];
        $filtered = array_diff_key($data, array_flip($sensitiveFields));
        
        $_SESSION['old_input'] = $filtered;
    }
}

if (!function_exists('clear_validation_data')) {
    /**
     * Clear validation errors and old input from session
     * 
     * @return void
     */
    function clear_validation_data() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset($_SESSION['form_errors']);
        unset($_SESSION['old_input']);
    }
}
?>
