// signup form validation 

document.addEventListener("DOMContentLoaded", function () {
  function validateInput(input) {
    var field = input;
    var value = field.value ? field.value.trim() : "";
    var errorfield = document.getElementById(field.getAttribute("name") + "_error");
    var validationType = field.getAttribute("data-validation");
    var minLength = parseInt(field.getAttribute("data-min") || "0", 10);
    var maxLength = parseInt(field.getAttribute("data-max") || "9999", 10);
    var fileSize = parseInt(field.getAttribute("data-filesize") || "0", 10);
    var fileType = field.getAttribute("data-filetype") || "";
    let errorMessage = "";
    var isFileInput = field.type === "file";
    var isCheckbox = field.type === "checkbox";

    if (!errorfield) {
        return true;
    }

    if (validationType) {
      // Required field validation (all types)
      if (validationType.includes("required")) {
        if (isCheckbox) {
          if (!field.checked) {
            errorMessage = "You must accept the terms and conditions.";
          }
        } else if (isFileInput) {
          if (!field.files || field.files.length === 0) {
            errorMessage = "This field is required.";
          }
        } else if (value === "" || value === "0" || value === null) {
          errorMessage = "This field is required.";
        }
      }

      // Only continue with other validations if field has a value
      if (value !== "" && !errorMessage) {
        // Minimum length validation
        if (validationType.includes("min") && value.length < minLength) {
          errorMessage = `This field must be at least ${minLength} characters long.`;
        }

        // Maximum length validation
        if (validationType.includes("max") && value.length > maxLength) {
          errorMessage = `This field must be at most ${maxLength} characters long.`;
        }

        if(validationType.includes('alphabetic'))
        {
          const alphabet_regex = /^[a-zA-Z\s]+$/;
          if(!alphabet_regex.test(value))
          {
            errorMessage = "Please enter alphabetic characters only.";
          }
        }

        // Email format validation
        if (validationType.includes("email")) {
          const emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w]{2,4}$/;
          if (!emailRegex.test(value)) {
            errorMessage = "Please enter a valid email address.";
          }
        }

        // Numeric value validation
        if (validationType.includes("number")) {
          const numberRegex = /^[0-9]+$/;
          if (!numberRegex.test(value)) {
            errorMessage = "Please enter only numbers.";
          }
        }

        // Strong password validation (at least 8 chars, 1 upper, 1 lower, 1 number)
        if (validationType.includes("strongPassword")) {
          const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[A-Za-z\d@$!%*?&]{8,}$/;
          if (!passwordRegex.test(value)) {
            errorMessage =
              "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.";
          }
        }

        // Password confirmation validation
        if (validationType.includes("confirmPassword")) {
          const form = field.closest('form');
          if (form) {
             const passwordField = form.querySelector('input[name="password"]');
             const passwordValue = passwordField ? passwordField.value : '';
             if (value !== passwordValue) {
               errorMessage = "Passwords do not match.";
             }
          }
        }

        // Dropdown selection validation
        if (validationType.includes("select") && (value === "" || value === "0" || value === null)) {
          errorMessage = "Please select an option.";
        }
      }

      // File validations (only if file is selected)
      if (isFileInput && field.files && field.files.length > 0) {
        const file = field.files[0];
        
        // File size validation
        if (validationType.includes("fileSize")) {
          if (file.size > fileSize * 1024) {
            errorMessage = `File size must be less than ${fileSize}KB.`;
          }
        }

        // File type validation
        if (validationType.includes("fileType") && !errorMessage) {
          const fileExtension = file.name.split(".").pop().toLowerCase();
          const allowedExtensions = fileType
            .split(",")
            .map((ext) => ext.trim().toLowerCase());
          if (!allowedExtensions.includes(fileExtension)) {
            errorMessage = `File type must be ${fileType}.`;
          }
        }
      }

      if (errorMessage) {
        errorfield.textContent = errorMessage;
        errorfield.style.display = "block";
        field.classList.add("is-invalid");
        field.classList.remove("is-valid");
        errorfield.classList.add("small", "text-danger");
        return false;
      } else {
        errorfield.textContent = "";
        errorfield.style.display = "none";
        field.classList.remove("is-invalid");
        field.classList.add("is-valid");
        return true;
      }
    }
    return true;
  }

  document.querySelectorAll("input, textarea, select").forEach(function (element) {
    element.addEventListener("input", function () { validateInput(this); });
    element.addEventListener("change", function () { validateInput(this); });
  });

  document.querySelectorAll("form").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      let isValid = true;
      this.querySelectorAll("input, textarea, select").forEach(function (element) {
        const fieldValid = validateInput(element);
        if (!fieldValid) {
          isValid = false;
        }
      });
      if (!isValid) {
        e.preventDefault();
      }
    });
  });
});

