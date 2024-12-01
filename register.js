 // Select the pop-up and profile button
 const popup = document.getElementById('sidePopup');
 const togglePopupButton = document.getElementById('togglePopup');

 // Toggle the pop-up visibility
 togglePopupButton.addEventListener('click', () => {
     // Toggle the active class for sliding in and out
     popup.classList.toggle('active');
 });