import { createReduxStore, register } from '@wordpress/data';

import actions from './actions';
import reducer from './reducer';
import resolvers from './resolvers';
import selectors from './selectors';

const STORE_NAME = 'dozuki/settings';

// @todo Resolvers should have matching selectors, and not combined with the actions.
// Combine actions and resolvers
const combinedActions = {
    ...actions,
    ...resolvers,
};

// Create and register the store
const store = createReduxStore(STORE_NAME, {
    reducer,
    actions: combinedActions,
    selectors,
});

register(store);

export default store;
export { STORE_NAME };
