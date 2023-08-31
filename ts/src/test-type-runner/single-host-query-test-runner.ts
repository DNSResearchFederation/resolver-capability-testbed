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
    async runTest(domainName, additionalConfig?: any, testRunCallback?: any) {

        let uuid = this.getUUID();
        let hostname = domainName;

        if (this._fixedSubdomains) {
            hostname = this._fixedSubdomains + "." + hostname;
        }

        for (let i = 0; i < this._dynamicSubdomains; i++) {
            hostname = uuid + '.' + hostname;
        }

        // Make a request and call the run callback with array of previous requests
        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname).then(() => {
            if (testRunCallback)
                // Pass the array of previous requests
                testRunCallback(this.getPreviousRequests());
        });


    }

}