/**
 * Mock session storage class
 */
export default class MockSessionStorage {

    // Store
    private _store: any = {};

    /**
     * Get an item
     *
     * @param key
     */
    public getItem(key: string) {
        return this._store[key];
    }


    /**
     * Get an item
     *
     * @param key
     */
    public setItem(key: string, value: string) {
        this._store[key] = value;
    }

}