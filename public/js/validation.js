//  sinupin form validation 

$(document).ready(function () {
  function validateInput(input) {
    var field = $(input);
    var value = field.val() ? field.val().trim() : "";
    var errorfield = $("#" + field.attr("name") + "_error");
    var validationType = field.data("validation");
    var minLength = field.data("min") || 0;
    var maxLength = field.data("max") || 9999;
    var fileSize = field.data("filesize") || 0;
    var fileType = field.data("filetype") || "";
    let errorMessage = "";
    var isFileInput = field.attr("type") === "file";
    var isCheckbox = field.attr("type") === "checkbox";

    if (validationType) {
      // Required field validation (all types)
      if (validationType.includes("required")) {
        if (isCheckbox) {
          if (!field.is(":checked")) {
            errorMessage = "You must accept the terms and conditions.";
          }
        } else if (isFileInput) {
          if (!field[0].files || field[0].files.length === 0) {
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
          alphabet_regex = /^[a-zA-Z\s]+$/;
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

        // Strong password validation (at least 8 chars, 1 upper, 1 lower, 1 number, 1 special)
        if (validationType.includes("strongPassword")) {
          const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
          if (!passwordRegex.test(value)) {
            errorMessage =
              "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
          }
        }

        // Password confirmation validation
        if (validationType.includes("confirmPassword")) {
          const confirmPassword = $("#" + field.attr("name") + "_confirm").val();
          if (value !== confirmPassword) {
            errorMessage = "Passwords do not match.";
          }
        }

        // Dropdown selection validation
        if (validationType.includes("select") && (value === "" || value === "0" || value === null)) {
          errorMessage = "Please select an option.";
        }
      }

      // File validations (only if file is selected)
      if (isFileInput && field[0].files && field[0].files.length > 0) {
        const file = field[0].files[0];
        
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
        errorfield.text(errorMessage).show();
        field.addClass("is-invalid").removeClass("is-valid");
        errorfield.addClass("small text-danger");
        return false;
      } else {
        errorfield.text("").hide();
        field.removeClass("is-invalid").addClass("is-valid");
        return true;
      }
    }
    return true;
  }
  $("input, textarea, select").on("input change", function () {
    validateInput(this);
  });

  $("form").on("submit", function (e) {
    let isValid = true;
    $(this)
      .find("input, textarea, select")
      .each(function () {
        const fieldValid = validateInput(this);
        if (!fieldValid) {
          isValid = false;
        }
      });
    if (!isValid) {
      e.preventDefault();
      return false;
    }
  });
});


