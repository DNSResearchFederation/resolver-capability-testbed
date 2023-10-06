import TtlTestRunner from "../../src/test-type-runner/ttl-test-runner";

describe("Test single host query test runner tests", function () {

    var originalTimeout;

    beforeEach(function() {
        originalTimeout = jasmine.DEFAULT_TIMEOUT_INTERVAL;
        jasmine.DEFAULT_TIMEOUT_INTERVAL = 11000;
    });

    it("Should be able to request with a unique prefix", async () => {

        let testRunner = new TtlTestRunner();
        testRunner.runTest("test.com", []);

        expect(testRunner.getPreviousRequests().length).toEqual(2)

        // First request to ttl10;
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("ttl10.test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("?request=1");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(69);

        // First request to ttl15
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("ttl15.test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("?request=1");
        expect(testRunner.getPreviousRequests()[1].hostname.length).toEqual(69);

        // Wait 10s
        await testRunner.wait(10000);

        expect(testRunner.getPreviousRequests().length).toEqual(4);

        // Second request to ttl10
        expect(testRunner.getPreviousRequests()[2].hostname).toContain("ttl10.test.com");
        expect(testRunner.getPreviousRequests()[2].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[2].hostname).toContain("?request=2");
        expect(testRunner.getPreviousRequests()[2].hostname.length).toEqual(69);

        // Second request to ttl15
        expect(testRunner.getPreviousRequests()[3].hostname).toContain("ttl15.test.com");
        expect(testRunner.getPreviousRequests()[3].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[3].hostname).toContain("?request=2");
        expect(testRunner.getPreviousRequests()[3].hostname.length).toEqual(69);

    });

    afterEach(function() {
        jasmine.DEFAULT_TIMEOUT_INTERVAL = originalTimeout;
    });

});