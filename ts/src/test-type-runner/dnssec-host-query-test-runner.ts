import TestTypeRunner from "./test-type-runner";

export default class DNSSECHostQueryTestRunner extends TestTypeRunner {

    /**
     * Run test for a single domain query
     *
     * @param domainName
     * @param additionalConfig
     * @param testRunCallback
     */
    runTest(domainName, additionalConfig?: any, testRunCallback?: any) {

        let uuid = this.getUUID();
        let hostname1 = uuid + "." + domainName;
        let hostname2 = uuid + "." + this.addDomainPrefix(domainName, "unsigned-")
        let hostname3 = uuid + "." + this.addDomainPrefix(domainName, "unvalidated-")

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname1).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname2).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname3).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

    }

}