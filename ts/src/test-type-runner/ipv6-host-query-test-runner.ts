import TestTypeRunner from "./test-type-runner";

export default class Ipv6HostQueryTestRunner extends TestTypeRunner {

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
        let hostname2 = uuid + "." + this.addDomainSuffix(domainName, "-ipv4")

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname1).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname2).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });
    }

}