import TtlTestRunner from "../../src/test-type-runner/ttl-test-runner";

describe("Test single host query test runner tests", function () {

    it("Should be able to request with a unique prefix", () => {

        let testRunner = new TtlTestRunner();
        testRunner.runTest("test.com", []);

        expect(testRunner.getPreviousRequests().length).toEqual(2)

        // First request to ttl10;
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("ttl10.test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(59);

        // First request to ttl15
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("ttl15.test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[1].hostname.length).toEqual(59);

        // Wait 10s
        testRunner.wait(10000);

        expect(testRunner.getPreviousRequests().length).toEqual(4);

        // Second request to ttl10
        expect(testRunner.getPreviousRequests()[2].hostname).toEqual(testRunner.getPreviousRequests()[0].hostname);

        // Second request to ttl15
        expect(testRunner.getPreviousRequests()[3].hostname).toEqual(testRunner.getPreviousRequests()[2].hostname);

    });

});