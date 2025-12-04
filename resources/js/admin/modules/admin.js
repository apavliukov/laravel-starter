import '#admin/bootstrap';
import { startAlpine, initAlpineComponents } from '#common/vendor/alpine';
import sample from '#common/components/sample';

class Admin {
    init = () => {
        initAlpineComponents({
            sample,
        });

        startAlpine();
    };
}

export default Admin;
