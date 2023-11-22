import TestTypeRunner from "./test-type-runner";

export default class NSECHostQueryTestRunner extends TestTypeRunner {

    /**
     * Run test for a single domain query
     *
     * @param domainName
     * @param additionalConfig
     * @param testRunCallback
     */
    runTest(domainName, additionalConfig?: any, testRunCallback?: any) {

        let hostname1 = "apples." + domainName;
        let hostname2 = "pears." + domainName;
        let hostname3 = "oranges." + domainName;

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname1).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname2).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

        this.wait(500);

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname3).then(() => {
            if (testRunCallback)
                testRunCallback(this.getPreviousRequests());
        });

    }

}