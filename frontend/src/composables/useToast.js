import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
export function openToast(message, type = 'info') {
  switch (type) {
    case 'success':
      toast.success(message);
      break;
    case 'error':
      toast.error(message);
      break;
    case 'warning':
      toast.warning(message);
      break;
    default:
      toast.info(message);
  }
}