"use strict";

import QRCode from "qrcode";

/**
 * Generates QR codes for elements with the "data-qr-url" attribute.
 * Customizes the QR codes based on additional data attributes provided.
 */
window.processQrCodes = function () {
  const elements = document.querySelectorAll("[data-qr-url]");

  elements.forEach(function (element) {
    const url = element.getAttribute("data-qr-url");
    
    // Skip if URL is empty or invalid
    if (!url || url.trim() === "" || url === "null" || url === "undefined") {
      return;
    }
    
    const colorLight = element.getAttribute("data-qr-color-light") || "#fff";
    const colorDark = element.getAttribute("data-qr-color-dark") || "#000";

    const opts = {
      errorCorrectionLevel: "H",
      type: "svg",
      margin: 2,
      color: {
        dark: colorDark,
        light: colorLight,
      },
      width: 512, // High resolution for crisp display
    };

    // Generate the QR code as SVG for perfect scaling
    QRCode.toString(
      url,
      opts,
      function (error, svgString) {
        if (error) {
          console.error(error);
        } else {
          // Convert SVG string to data URL
          const svgDataUrl = 'data:image/svg+xml;base64,' + btoa(svgString);
          element.src = svgDataUrl;
        }
      }
    );
  });
};


window.processQrCodes();
