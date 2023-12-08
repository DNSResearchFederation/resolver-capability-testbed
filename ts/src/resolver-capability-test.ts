import SingleHostQueryTestRunner from "./test-type-runner/single-host-query-test-runner";
import TestTypeRunner from "./test-type-runner/test-type-runner";
import MockSessionStorage from "./util/mock-session-storage";
import TtlTestRunner from "./test-type-runner/ttl-test-runner";
import Ipv6HostQueryTestRunner from "./test-type-runner/ipv6-host-query-test-runner";
import DNSSECHostQueryTestRunner from "./test-type-runner/dnssec-host-query-test-runner";
import NSECHostQueryTestRunner from "./test-type-runner/nsec-host-query-test-runner";

export default class ResolverCapabilityTest {

    // Test type runners
    private testTypeRunners: any = {
        "ipv6": new Ipv6HostQueryTestRunner(),
        "qname-minimisation": new SingleHostQueryTestRunner(1, "qname.resolver.capability"),
        "minimum-ttl": new TtlTestRunner(),
        "dnssec": new DNSSECHostQueryTestRunner(),
        "tcp-fallback": new SingleHostQueryTestRunner(1, "b9599170-17cc-46ab-b01c-67c2731f8dab.b9599170-17cc-46ab-b01c-67c2731f8dab.b9599170-17cc-46ab-b01c-67c2731f8dab.b9599170-17cc-46ab-b01c-67c2731f8dab.b9599170-17cc-46ab-b01c-67c2731f8dab"),
        "aggressive-nsec": new NSECHostQueryTestRunner()
    };

    // Export test type runner
    public static TestTypeRunner = TestTypeRunner;

    // Create mock session storage
    public static mockSessionStorage = new MockSessionStorage();

    /**
     * Construct with a test type, domain name and any other config (if required).
     * Start immediately.
     *
     * @param testType
     * @param domainName
     * @param additionalTestConfig
     * @param testRunCallback
     */
    constructor(testType: string, domainName: string, additionalTestConfig: any, testRunCallback: any) {

        // Ensure we create additional test config
        if (!additionalTestConfig)
            additionalTestConfig = {};

        // If multipleRequestsPerSession not set or not first hit from session storage, quit
        let sessionKey = "resolvertest." + testType + "." + domainName;
        if (!additionalTestConfig.multipleRequestsPerSession) {
            if (this.sessionStorage.getItem(sessionKey))
                return;
            else
                this.sessionStorage.setItem(sessionKey, "1");
        }


        this.testTypeRunners[testType].runTest(domainName, additionalTestConfig, testRunCallback);
    }

    /**
     * Return installed test type runners
     */
    get installedTestTypeRunners() {
        return this.testTypeRunners;
    }


    /**
     * Get the session storage object - useful for testing
     */
    get sessionStorage(): any {
        if (typeof sessionStorage !== 'undefined') {
            return sessionStorage;
        } else {
            return ResolverCapabilityTest.mockSessionStorage;
        }
    }

}