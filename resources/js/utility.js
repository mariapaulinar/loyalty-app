"use strict";

/**
 * Check if the cookie with the given name exists.
 *
 * @param {string} cookieName - The name of the cookie to check.
 * @return {boolean} True if the cookie exists, false otherwise.
 */
window.checkCookie = function(cookieName) {
  let cookieArr = document.cookie.split(';');

  for(let i = 0; i < cookieArr.length; i++) {
      let cookiePair = cookieArr[i].split("=");

      if(cookieName == cookiePair[0].trim()) {
          return true;
      }
  }
  return false;
}

/**
* Set a cookie with the given name.
*
* @param {string} cookieName - The name of the cookie to set.
* @param {string} cookieValue - The value of the cookie to set.
* @param {number} expDays - The number of days until the cookie should expire.
*/
window.setCookie = function(cookieName, cookieValue, expDays) {
  let date = new Date();
  date.setTime(date.getTime() + (expDays * 24 * 60 * 60 * 1000));
  let expires = "expires="+ date.toUTCString();
  document.cookie = cookieName + "=" + cookieValue + ";" + expires + ";path=/";
}

/**
 * Sets up click event listeners on all elements with a 'data-clickable-href' attribute after the DOM is fully loaded.
 * When an element is clicked, the browser is redirected to the URL stored in the element's 'data-clickable-href' attribute.
 *
 * @listens document:DOMContentLoaded
 */
document.addEventListener("DOMContentLoaded", function() {
  const elements = document.querySelectorAll("[data-clickable-href]");
  elements.forEach(element => {
      element.addEventListener("click", function() {
          window.location = element.dataset.clickableHref;
      });
  });
});

/**
 * Converts a hex color to RGBA format with opacity.
 *
 * @param {string} hex - The hex color to convert.
 * @param {number} [opacity=1] - The opacity for the RGBA output (default is 1).
 * @returns {string} The RGBA color value.
 */
window.hexToRGBA = function (hex, opacity = 1) {
  // Remove the hash from the hex color if it's there
  hex = hex.replace('#', '');

  // Convert the hex color to RGB
  const r = parseInt(hex.slice(0, 2), 16);
  const g = parseInt(hex.slice(2, 4), 16);
  const b = parseInt(hex.slice(4, 6), 16);

  // Return the color in RGBA format
  return `rgba(${r},${g},${b},${opacity})`;
};

/**
 * Processes card backgrounds, converting data-bg-color to RGBA and applying it with optional opacity and data-bg-image.
 */
window.processCardBackgrounds = function () {
  const elements = document.querySelectorAll("[data-bg-color]");

  elements.forEach((element) => {
    const bgColor = element.getAttribute("data-bg-color");
    const opacity = element.getAttribute("data-bg-opacity");
    const bgColorRGBA = window.hexToRGBA(bgColor, opacity);
    const bgImg = element.getAttribute("data-bg-image");

    const backgroundImage = bgImg
      ? `linear-gradient(${bgColorRGBA}, ${bgColorRGBA}), url('${bgImg}')`
      : `linear-gradient(${bgColorRGBA}, ${bgColorRGBA})`;

    element.style.setProperty("background-image", backgroundImage);
    element.classList.remove(bgColor);
    element.classList.add("bg-white");
  });
};

window.processCardBackgrounds();

/**
 * Generate a random password of a given length.
 *
 * @param {number} length - The desired length of the generated password.
 * @return {string} The randomly generated password.
 */
window.generatePassword = (length) => {
  // Define the character set for password generation
  // const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
  const charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%&";
  
  // Initialize the password
  let password = "";

  // Generate password of given length
  for (let i = 0; i < length; ++i) {
    // Pick a random character from the charset and add to the password
    password += charset[Math.floor(Math.random() * charset.length)];
  }
  
  // Return the generated password
  return password;
};
