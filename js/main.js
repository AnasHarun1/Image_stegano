document.addEventListener("DOMContentLoaded", function () {
  const embedForm = document.getElementById("embedForm");
  const extractForm = document.getElementById("extractForm");
  const imagePreview = document.getElementById("imagePreview");
  const logoPreview = document.getElementById("logoPreview");
  const watermarkedImagePreview = document.getElementById(
    "watermarkedImagePreview"
  );

  // Image preview functionality
  function setupImagePreview(fileInput, previewImg) {
    fileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          previewImg.src = event.target.result;
          previewImg.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Setup image previews
  setupImagePreview(document.getElementById("image"), imagePreview);
  setupImagePreview(document.getElementById("logo"), logoPreview);
  setupImagePreview(
    document.getElementById("watermarked-image"),
    watermarkedImagePreview
  );

  // AJAX form submission
  function setupAjaxFormSubmission(form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(form);
      const resultContainer = document.createElement("div");
      resultContainer.classList.add("mt-4", "p-4", "bg-gray-100", "rounded");

      fetch("process.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            resultContainer.classList.add("text-green-600");
            resultContainer.innerHTML = `
                        <h3 class="font-bold">Success</h3>
                        <p>${data.message}</p>
                        ${
                          data.data.watermarked_image
                            ? `<img src="${data.data.watermarked_image}" class="mt-4 max-h-64">`
                            : data.data.message
                            ? `<p>Message: ${data.data.message}</p>`
                            : ""
                        }
                    `;
          } else {
            resultContainer.classList.add("text-red-600");
            resultContainer.innerHTML = `
                        <h3 class="font-bold">Error</h3>
                        <p>${data.message}</p>
                    `;
          }
          form.appendChild(resultContainer);
        })
        .catch((error) => {
          console.error("Error:", error);
          resultContainer.classList.add("text-red-600");
          resultContainer.innerHTML = `
                    <h3 class="font-bold">Network Error</h3>
                    <p>Unable to process your request. Please try again.</p>
                `;
          form.appendChild(resultContainer);
        });
    });
  }

  // Setup AJAX for both forms
  setupAjaxFormSubmission(embedForm);
  setupAjaxFormSubmission(extractForm);

  // Password toggle (existing functionality)
  const passwordToggles = document.querySelectorAll(".toggle-password");
  passwordToggles.forEach((toggle) => {
    toggle.addEventListener("click", function () {
      const passwordInput = this.closest(".relative").querySelector("input");
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.classList.toggle("fa-eye-slash");
      this.classList.toggle("fa-eye");
    });
  });
});
// Drag-and-drop functionality for the extract section
const extractInput = document.getElementById("watermarked-image");
const extractPreview = document.getElementById("watermarkedImagePreview");
const extractDropArea = extractInput.closest(".border-dashed");

extractDropArea.addEventListener("dragover", function (e) {
  e.preventDefault();
  extractDropArea.classList.add("bg-gray-100");
});

extractDropArea.addEventListener("dragleave", function () {
  extractDropArea.classList.remove("bg-gray-100");
});

extractDropArea.addEventListener("drop", function (e) {
  e.preventDefault();
  extractDropArea.classList.remove("bg-gray-100");
  const file = e.dataTransfer.files[0];
  extractInput.files = e.dataTransfer.files; // Assign dropped files to input
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      extractPreview.src = e.target.result;
      extractPreview.classList.remove("hidden");
      extractDropArea.style.display = "none"; // Hide the drag-and-drop area
    };
    reader.readAsDataURL(file);
  }
});

// File input change handling for extract
extractInput.addEventListener("change", function (event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      extractPreview.src = e.target.result;
      extractPreview.classList.remove("hidden");
      extractDropArea.style.display = "none"; // Hide the drag-and-drop area
    };
    reader.readAsDataURL(file);
  }
});
