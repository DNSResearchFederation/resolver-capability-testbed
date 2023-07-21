import TestTypeRunner from "./test-type-runner";

export default class SingleHostQueryTestRunner extends TestTypeRunner {

    // Number of subdomains
    private _numberOfSubdomains: number;

    constructor(numberOfSubdomains = 1) {
        super();
        this._numberOfSubdomains = numberOfSubdomains;
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

        for (let i = 0; i < this._numberOfSubdomains; i++) {
            hostname = uuid + '.' + hostname;
        }

        if (additionalConfig.insecure)
            this.request("http://" + hostname);
        else
            this.request("https://" + hostname);

    }

}