import ResolverCapabilityTest from "../src/resolver-capability-test";

describe("Test entry point", function () {

    it("Should be able to run the correct test type for ipv6", () => {

        let resolverCapTest = new ResolverCapabilityTest("ipv6", "test.com", [], null);
        expect(resolverCapTest.installedTestTypeRunners["ipv6"].getPreviousRequests().length).toEqual(2);
        expect(resolverCapTest.installedTestTypeRunners["ipv6"].getPreviousRequests()[0].hostname).toContain("test.com");
        expect(resolverCapTest.installedTestTypeRunners["ipv6"].getPreviousRequests()[1].hostname).toContain("ipv4-test.com")

    });

    it("Should be able to run the correct test type for qname-minimisation", () => {

        let resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test.com", [], null);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests().length).toEqual(1);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests()[0].hostname).toContain("test.com");

    });

    it("Should be able to run the correct test type for minimum-ttl", () => {

        let resolverCapTest = new ResolverCapabilityTest("minimum-ttl", "test.com", [], null);
        expect(resolverCapTest.installedTestTypeRunners["minimum-ttl"].getPreviousRequests().length).toEqual(2);
        expect(resolverCapTest.installedTestTypeRunners["minimum-ttl"].getPreviousRequests()[0].hostname).toContain("test.com");

    });

    it('Should be able to register a callback on test completion', () => {
        let resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test3.com", [], (requests) => {
            expect(requests.length).toEqual(1);
            expect(requests[0].hostname).toContain("test3.com");
        });

    });

    it("Should only run test once for given type and domain name by default unless multipleRequestsPerSession set in config", () => {

        // Default behaviour of single request per session
        let resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test1.com", {}, null);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests().length).toEqual(1);

        expect(resolverCapTest.sessionStorage.getItem("resolvertest.qname-minimisation.test1.com")).toBeDefined();

        // Expect this request to be suppressed
        resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test1.com", {}, null);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests().length).toEqual(0);


        // If set with multiple flag, confirm multiple requests possible
        resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test2.com", {"multipleRequestsPerSession": true}, null);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests().length).toEqual(1);

        resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test2.com", {"multipleRequestsPerSession": true}, null);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests().length).toEqual(1);


    });


});