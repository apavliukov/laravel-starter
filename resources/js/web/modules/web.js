import '#web/bootstrap';
import { initAlpineComponents, startAlpine } from '#common/vendor/alpine';
import sample from '#common/components/sample';

class Web {
    init = () => {
        initAlpineComponents({
            sample,
        });

        startAlpine();
    };
}

export default Web;
