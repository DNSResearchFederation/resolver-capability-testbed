import TestTypeRunner from "./test-type-runner";
import SingleHostQueryTestRunner from "./single-host-query-test-runner";

export default class TtlTestRunner extends TestTypeRunner {

    constructor() {
        super();
    }

    async runTest(domainName, additionalConfig?: any, testRunCallback?: any) {

        let uuid = this.getUUID();
        let hostname10 = uuid + ".ttl10." + domainName;
        let hostname15 = uuid + ".ttl15." + domainName;


        this.doubleRequest(hostname10, additionalConfig, testRunCallback, 5000)
        this.doubleRequest(hostname15, additionalConfig, testRunCallback, 10000)

    }

    async doubleRequest(hostname, additionalConfig, testRunCallback, waitTime): Promise<void> {

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname + "?request=1").then(() => {
            if (testRunCallback)
                // Pass the array of previous requests
                testRunCallback(this.getPreviousRequests());
        });

        await this.wait(waitTime);

        this.request("http" + (additionalConfig.insecure ? "" : "s") + "://" + hostname + "?request=2").then(() => {
            if (testRunCallback)
                // Pass the array of previous requests
                testRunCallback(this.getPreviousRequests());
        });

    }

}
