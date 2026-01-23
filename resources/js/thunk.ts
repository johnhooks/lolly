/* eslint-disable @typescript-eslint/no-explicit-any */
import {
    invalidateResolution,
    invalidateResolutionForStore,
    invalidateResolutionForStoreSelector,
} from '@wordpress/data/build-types/redux-store/metadata/actions';
import type {
    ActionCreatorsOf as BaseActionCreatorsOf,
    AnyConfig,
    CurriedSelectorsOf,
    StoreDescriptor,
} from '@wordpress/data/build-types/types';
import type { Action } from 'redux';

type InvalidateResolution = typeof invalidateResolution;
type InvalidateResolutionForStore = typeof invalidateResolutionForStore;
type InvalidateResolutionForStoreSelector =
    typeof invalidateResolutionForStoreSelector;

type InvalidateResolutionAction = ReturnType<InvalidateResolution>;
type InvalidateResolutionForStoreAction =
    ReturnType<InvalidateResolutionForStore>;
type InvalidateResolutionForStoreSelectorAction =
    ReturnType<InvalidateResolutionForStoreSelector>;

/**
 * The action creators for metadata actions.
 */
type MetadataActionCreators = {
    invalidateResolution: InvalidateResolution;
    invalidateResolutionForStore: InvalidateResolutionForStore;
    invalidateResolutionForStoreSelector: InvalidateResolutionForStoreSelector;
};

/**
 * Dispatchable metadata actions.
 */
type MetadataAction =
    | InvalidateResolutionAction
    | InvalidateResolutionForStoreAction
    | InvalidateResolutionForStoreSelectorAction;

export type PromisifiedSelectorsOf<S> =
    S extends StoreDescriptor<AnyConfig>
        ? {
              [key in keyof CurriedSelectorsOf<S>]: PromisifySelectorOf<
                  CurriedSelectorsOf<S>[key]
              >;
          }
        : never;

type PromisifySelectorOf<F extends (...args: any[]) => any> = F extends (
    ...args: infer P
) => infer R
    ? (...args: P) => Promise<R>
    : F;

/**
 * The action creators for a store descriptor.
 *
 * Also includes metadata actions creators.
 */
type ActionCreatorsOf<C extends AnyConfig> = BaseActionCreatorsOf<C> &
    MetadataActionCreators;

/**
 * Dispatchable action creators for a store descriptor.
 */
export type RegistryDispatch<S extends string | StoreDescriptor<AnyConfig>> = (
    storeNameOrDescriptor: S
) => S extends StoreDescriptor<infer C> ? ActionCreatorsOf<C> : unknown;

/**
 * Selectors for a store descriptor.
 */
export type RegistrySelect<S extends string | StoreDescriptor<AnyConfig>> = (
    storeNameOrDescriptor: S
) => S extends StoreDescriptor<infer C> ? CurriedSelectorsOf<C> : unknown;

/**
 * Dispatch an action to the configured store.
 */
export type DispatchFunction<A extends Action> = (
    action: A | MetadataAction
) => void;

/**
 * A redux store registry.
 */
export type Registry = {
    dispatch: <S extends string | StoreDescriptor<AnyConfig>>(
        storeNameOrDescriptor: S
    ) => S extends StoreDescriptor<infer C> ? ActionCreatorsOf<C> : unknown;
    select: <S extends string | StoreDescriptor<AnyConfig>>(
        storeNameOrDescriptor: S
    ) => S extends StoreDescriptor<infer C> ? CurriedSelectorsOf<C> : unknown;
};

/**
 * Thunk arguments.
 */
export type ThunkArgs<
    A extends Action,
    S extends StoreDescriptor<AnyConfig>,
> = {
    /**
     * Dispatch an action to the store.
     */
    dispatch: (S extends StoreDescriptor<infer Config>
        ? ActionCreatorsOf<Config>
        : unknown) &
        DispatchFunction<A>;

    /**
     * Selectors for the store.
     */
    select: CurriedSelectorsOf<S>;

    /**
     * Selectors for the store that return a promise awaiting their resolver.
     */
    resolveSelect: PromisifiedSelectorsOf<S>;

    /**
     * The store registry object.
     */
    registry: Registry;
};

/**
 * Thunk.
 */
export type Thunk<
    A extends Action,
    S extends StoreDescriptor<AnyConfig>,
    T = void,
> =
    T extends Awaited<infer R>
        ? (args: ThunkArgs<A, S>) => Promise<R>
        : (args: ThunkArgs<A, S>) => T;
