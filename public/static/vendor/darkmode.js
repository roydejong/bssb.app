!function() {
  const switchElement = document.getElementById("darkSwitch");
  let currentTheme = localStorage.getItem("darkSwitch");

  let fnUpdate = () => {
    localStorage.setItem("darkSwitch", currentTheme);
    if (currentTheme === "light") {
      switchElement.checked = false;
      document.body.setAttribute("data-theme", "light");
    } else {
      switchElement.checked = true;
      document.body.setAttribute("data-theme", "dark");
    }
  };

  switchElement.addEventListener("change", function() {
    currentTheme = switchElement.checked ? "dark" : "light";
    fnUpdate();
  });
  fnUpdate();
}();