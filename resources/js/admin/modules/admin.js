import '#admin/bootstrap';
import { startAlpine, initAlpineComponents } from '#common/vendor/alpine';
import toast from '#admin/components/toast';

class Admin {
    init = () => {
        initAlpineComponents({
            toast,
        });

        startAlpine();
    };
}

export default Admin;
