<?php
/**
 * Form Helper Functions
 * 
 * Reusable functions for rendering and validating HTML forms.
 * Provides consistent form field generation with proper escaping.
 * 
 * @package RipalDesign
 * @subpackage Components
 */

if (!function_exists('form_start')) {
    /**
     * Generate form opening tag
     * 
     * @param string $action Form action URL
     * @param string $method HTTP method (POST, GET)
     * @param array $attrs Additional attributes
     * @return string Form opening tag HTML
     */
    function form_start($action, $method = 'POST', $attrs = []) {
        $method = strtoupper($method);
        $output = '<form action="' . esc_attr($action) . '" method="' . esc_attr($method) . '"';
        
        foreach ($attrs as $key => $value) {
            $output .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        $output .= '>';
        
        // Add CSRF token for POST requests
        if ($method === 'POST' && function_exists('csrf_token_field')) {
            $output .= csrf_token_field();
        }
        
        return $output;
    }
}

if (!function_exists('form_end')) {
    /**
     * Generate form closing tag
     * 
     * @return string Form closing tag HTML
     */
    function form_end() {
        return '</form>';
    }
}

if (!function_exists('form_input')) {
    /**
     * Generate text input field
     * 
     * @param string $name Input name
     * @param string $value Default value
     * @param array $attrs Additional attributes
     * @return string Input field HTML
     */
    function form_input($name, $value = '', $attrs = []) {
        $type = $attrs['type'] ?? 'text';
        $class = $attrs['class'] ?? 'form-control';
        $id = $attrs['id'] ?? $name;
        
        $output = '<input type="' . esc_attr($type) . '" ';
        $output .= 'name="' . esc_attr($name) . '" ';
        $output .= 'id="' . esc_attr($id) . '" ';
        $output .= 'value="' . esc_attr($value) . '" ';
        $output .= 'class="' . esc_attr($class) . '"';
        
        foreach ($attrs as $key => $val) {
            if (!in_array($key, ['type', 'class', 'id', 'name', 'value'])) {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        
        $output .= '>';
        
        return $output;
    }
}

if (!function_exists('form_textarea')) {
    /**
     * Generate textarea field
     * 
     * @param string $name Textarea name
     * @param string $value Default value
     * @param array $attrs Additional attributes
     * @return string Textarea HTML
     */
    function form_textarea($name, $value = '', $attrs = []) {
        $class = $attrs['class'] ?? 'form-control';
        $id = $attrs['id'] ?? $name;
        $rows = $attrs['rows'] ?? 4;
        
        $output = '<textarea ';
        $output .= 'name="' . esc_attr($name) . '" ';
        $output .= 'id="' . esc_attr($id) . '" ';
        $output .= 'class="' . esc_attr($class) . '" ';
        $output .= 'rows="' . esc_attr($rows) . '"';
        
        foreach ($attrs as $key => $val) {
            if (!in_array($key, ['class', 'id', 'name', 'rows'])) {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        
        $output .= '>' . esc($value) . '</textarea>';
        
        return $output;
    }
}

if (!function_exists('form_select')) {
    /**
     * Generate select dropdown field
     * 
     * @param string $name Select name
     * @param array $options Options array [value => label]
     * @param string $selected Selected value
     * @param array $attrs Additional attributes
     * @return string Select HTML
     */
    function form_select($name, $options, $selected = '', $attrs = []) {
        $class = $attrs['class'] ?? 'form-select';
        $id = $attrs['id'] ?? $name;
        
        $output = '<select ';
        $output .= 'name="' . esc_attr($name) . '" ';
        $output .= 'id="' . esc_attr($id) . '" ';
        $output .= 'class="' . esc_attr($class) . '"';
        
        foreach ($attrs as $key => $val) {
            if (!in_array($key, ['class', 'id', 'name'])) {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        
        $output .= '>';
        
        foreach ($options as $value => $label) {
            $isSelected = ($value == $selected) ? ' selected' : '';
            $output .= '<option value="' . esc_attr($value) . '"' . $isSelected . '>';
            $output .= esc($label);
            $output .= '</option>';
        }
        
        $output .= '</select>';
        
        return $output;
    }
}

if (!function_exists('form_checkbox')) {
    /**
     * Generate checkbox input
     * 
     * @param string $name Checkbox name
     * @param string $value Checkbox value
     * @param bool $checked Whether checkbox is checked
     * @param string $label Label text
     * @param array $attrs Additional attributes
     * @return string Checkbox HTML
     */
    function form_checkbox($name, $value, $checked = false, $label = '', $attrs = []) {
        $class = $attrs['class'] ?? 'form-check-input';
        $id = $attrs['id'] ?? $name . '_' . $value;
        
        $output = '<div class="form-check">';
        $output .= '<input type="checkbox" ';
        $output .= 'name="' . esc_attr($name) . '" ';
        $output .= 'id="' . esc_attr($id) . '" ';
        $output .= 'value="' . esc_attr($value) . '" ';
        $output .= 'class="' . esc_attr($class) . '"';
        
        if ($checked) {
            $output .= ' checked';
        }
        
        foreach ($attrs as $key => $val) {
            if (!in_array($key, ['class', 'id', 'name', 'value', 'checked'])) {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        
        $output .= '>';
        
        if ($label) {
            $output .= '<label class="form-check-label" for="' . esc_attr($id) . '">';
            $output .= esc($label);
            $output .= '</label>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

if (!function_exists('form_radio')) {
    /**
     * Generate radio input
     * 
     * @param string $name Radio name
     * @param string $value Radio value
     * @param bool $checked Whether radio is checked
     * @param string $label Label text
     * @param array $attrs Additional attributes
     * @return string Radio HTML
     */
    function form_radio($name, $value, $checked = false, $label = '', $attrs = []) {
        $class = $attrs['class'] ?? 'form-check-input';
        $id = $attrs['id'] ?? $name . '_' . $value;
        
        $output = '<div class="form-check">';
        $output .= '<input type="radio" ';
        $output .= 'name="' . esc_attr($name) . '" ';
        $output .= 'id="' . esc_attr($id) . '" ';
        $output .= 'value="' . esc_attr($value) . '" ';
        $output .= 'class="' . esc_attr($class) . '"';
        
        if ($checked) {
            $output .= ' checked';
        }
        
        foreach ($attrs as $key => $val) {
            if (!in_array($key, ['class', 'id', 'name', 'value', 'checked'])) {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        
        $output .= '>';
        
        if ($label) {
            $output .= '<label class="form-check-label" for="' . esc_attr($id) . '">';
            $output .= esc($label);
            $output .= '</label>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

if (!function_exists('form_button')) {
    /**
     * Generate button
     * 
     * @param string $text Button text
     * @param string $type Button type (button, submit, reset)
     * @param array $attrs Additional attributes
     * @return string Button HTML
     */
    function form_button($text, $type = 'submit', $attrs = []) {
        $class = $attrs['class'] ?? 'btn btn-primary';
        
        $output = '<button type="' . esc_attr($type) . '" ';
        $output .= 'class="' . esc_attr($class) . '"';
        
        foreach ($attrs as $key => $val) {
            if (!in_array($key, ['class', 'type'])) {
                $output .= ' ' . esc_attr($key) . '="' . esc_attr($val) . '"';
            }
        }
        
        $output .= '>';
        $output .= esc($text);
        $output .= '</button>';
        
        return $output;
    }
}

if (!function_exists('form_error')) {
    /**
     * Display form validation error
     * 
     * @param string $field Field name
     * @param array|null $errors Errors array
     * @return string Error HTML
     */
    function form_error($field, $errors = null) {
        if ($errors === null) {
            $errors = $_SESSION['form_errors'] ?? [];
        }
        
        if (!empty($errors[$field])) {
            return '<div class="invalid-feedback d-block">' . esc($errors[$field]) . '</div>';
        }
        
        return '';
    }
}

if (!function_exists('old')) {
    /**
     * Get old form value (for re-populating after validation error)
     * 
     * @param string $field Field name
     * @param string $default Default value
     * @return string Old value or default
     */
    function old($field, $default = '') {
        if (isset($_SESSION['old_input'][$field])) {
            return $_SESSION['old_input'][$field];
        }
        
        return $default;
    }
}
?>
