import './bootstrap';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import axios from 'axios';

function calderaSetTheme()
{
   if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
}

function notyfSuccess($msg)
{
   const notyf = new Notyf({
      duration: 5000,
      position: {
         x:'center',
         y:'top',
      }
   });
   notyf.success($msg);
}

function notyfError($msg)
{
   const notyf = new Notyf({
      duration: 5000,
      position: {
         x:'center',
         y:'top',
      }
   });
   notyf.error($msg);
}

const escKey = new KeyboardEvent('keydown', {
   key: 'Escape',
   keyCode: 27,
   which: 27,
   code: 'Escape',
});

calderaSetTheme()
window.calderaSetTheme  = calderaSetTheme;
window.notyfSuccess     = notyfSuccess;
window.notyfError       = notyfError;
window.axios            = axios;
window.escKey = escKey;