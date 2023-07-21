import SingleHostQueryTestRunner from "../../src/test-type-runner/single-host-query-test-runner";

describe("Test single host query test runner tests", function () {

    it("Should be able to request with a unique prefix", () => {

        let testRunner = new SingleHostQueryTestRunner();
        testRunner.runTest("test.com", []);
        expect(testRunner.getPreviousRequests().length).toEqual(1);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(53);

    });

    it("Should be able to request with multiple subdomains", () => {

        let testRunner = new SingleHostQueryTestRunner(3);
        testRunner.runTest("test.com", []);
        expect(testRunner.getPreviousRequests().length).toEqual(1);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(127);

    });

    it("Should be able perform as insecure request", () => {

        let testRunner = new SingleHostQueryTestRunner();
        testRunner.runTest("test.com", {"insecure": true});
        expect(testRunner.getPreviousRequests().length).toEqual(1);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("http://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(52);

        testRunner.runTest("test.com", {"insecure": false});
        expect(testRunner.getPreviousRequests().length).toEqual(2);
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("https://");

    });

});