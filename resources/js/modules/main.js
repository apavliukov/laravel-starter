import '#bootstrap';
import { startAlpine } from '#common/vendor/alpine';

class Main {
    init = () => {
        document.addEventListener('DOMContentLoaded', () => {
            startAlpine();
        });
    };
}

export default Main;
