import TestTypeRunner from "./test-type-runner";

export default class SingleHostQueryTestRunner extends TestTypeRunner {

    // Number of subdomains
    private _dynamicSubdomains: number;

    // A fixed subdomain to append to
    private _fixedSubdomains: string;

    constructor(dynamicSubdomains = 1, fixedSubdomain = null) {
        super();
        this._dynamicSubdomains = dynamicSubdomains;
        this._fixedSubdomains = fixedSubdomain;
    }


    /**
     * Run test for a single domain query
     *
     * @param domainName
     * @param additionalConfig
     */
    async runTest(domainName, additionalConfig?: any) {

        let uuid = this.getUUID();
        let hostname = domainName;

        if (this._fixedSubdomains) {
            hostname = this._fixedSubdomains + "." + hostname;
        }

        for (let i = 0; i < this._dynamicSubdomains; i++) {
            hostname = uuid + '.' + hostname;
        }

        if (additionalConfig.insecure)
            this.request("http://" + hostname);
        else
            this.request("https://" + hostname);

    }

}