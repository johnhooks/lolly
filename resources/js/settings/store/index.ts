import { createReduxStore, register } from '@wordpress/data';

import * as actions from './actions';
import { STORE_NAME } from './constants';
import reducer, { initializeDefaultState } from './reducer';
import * as resolvers from './resolvers';
import * as selectors from './selectors';

export const store = createReduxStore(STORE_NAME, {
    actions,
    selectors,
    reducer,
    resolvers,
    initialState: initializeDefaultState(),
});

register(store);
