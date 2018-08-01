import {observable} from 'core/mobx';

class TemplateViewStore {
    constructor() {
        this.store = observable({});
    }

    get(id) {
        return this.store[id];
    }

    set(id, context) {
        this.store[id] = context;
    }
}

let store = new TemplateViewStore();
export default store;