import SingleHostQueryTestRunner from "./test-type-runner/single-host-query-test-runner";

export default class ResolverCapabilityTest {

    // Test type runners
    private testTypeRunners: any = {
        "ipv6": new SingleHostQueryTestRunner(),
        "qname-minimisation": new SingleHostQueryTestRunner(1, "qname.resolver.test")
    };

    /**
     * Construct with a test type, domain name and any other config (if required).
     * Start immediately.
     *
     * @param testType
     * @param domainName
     * @param additionalTestConfig
     */
    constructor(testType: string, domainName: string, additionalTestConfig: any) {
        this.testTypeRunners[testType].runTest(domainName, additionalTestConfig);
    }

    /**
     * Return installed test type runners
     */
    get installedTestTypeRunners() {
        return this.testTypeRunners;
    }



}