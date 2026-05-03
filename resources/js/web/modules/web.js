import '#web/bootstrap';
import { initAlpineComponents, startAlpine } from '#common/vendor/alpine';

class Web {
    init = () => {
        initAlpineComponents({
            //
        });

        startAlpine();
    };
}

export default Web;
