import ResolverCapabilityTest from "../src/resolver-capability-test";

describe("Test entry point", function () {

    it("Should be able to run the correct test type for ipv6", () => {

        let resolverCapTest = new ResolverCapabilityTest("ipv6", "test.com", []);
        expect(resolverCapTest.installedTestTypeRunners["ipv6"].getPreviousRequests().length).toEqual(1);
        expect(resolverCapTest.installedTestTypeRunners["ipv6"].getPreviousRequests()[0].hostname).toContain("test.com");

    });

    it("Should be able to run the correct test type for qname-minimisation", () => {

        let resolverCapTest = new ResolverCapabilityTest("qname-minimisation", "test.com", []);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests().length).toEqual(1);
        expect(resolverCapTest.installedTestTypeRunners["qname-minimisation"].getPreviousRequests()[0].hostname).toContain("test.com");

    });

});