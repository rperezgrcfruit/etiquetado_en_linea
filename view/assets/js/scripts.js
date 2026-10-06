document.addEventListener('DOMContentLoaded', function () {
    
    if (document.getElementById("form-login") != null) {
        document.getElementById("form-login").addEventListener("submit", function () {
          document.getElementById("btn-login").textContent = "Validando..";
          document.getElementById("spinner").style.display = "block";
          document.getElementById("overlay").style.display = "block";
        });
      }

      if (document.querySelector('.toggle-password')){
        document.querySelector('.toggle-password').addEventListener('click', function() {
          const icon = this.querySelector('i');
          const input = document.querySelector('.password');
          if (icon.classList.contains('fa-eye')) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            input.setAttribute('type', 'text');
          } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            input.setAttribute('type', 'password');
          }
        });
      }  
})

  




