import DNSSECHostQueryTestRunner from "../../src/test-type-runner/dnssec-host-query-test-runner";
describe("Test ipv6 host query test runner", function ()  {

    it("Should be able to make 2 request to the correct domains", () => {

        let testRunner = new DNSSECHostQueryTestRunner();
        testRunner.runTest("test.com", []);

        expect(testRunner.getPreviousRequests().length).toEqual(3);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(53);

        expect(testRunner.getPreviousRequests()[1].hostname).toContain("unsigned-test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[1].hostname.length).toEqual(62);

        expect(testRunner.getPreviousRequests()[2].hostname).toContain("unvalidated-test.com");
        expect(testRunner.getPreviousRequests()[2].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[2].hostname.length).toEqual(65);

    })

    it("Should be able perform insecure requests", () => {

        let testRunner = new DNSSECHostQueryTestRunner();
        testRunner.runTest("test.com", {"insecure": true});

        expect(testRunner.getPreviousRequests().length).toEqual(3);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("http://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(52);

        expect(testRunner.getPreviousRequests()[1].hostname).toContain("unsigned-test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("http://");
        expect(testRunner.getPreviousRequests()[1].hostname.length).toEqual(61);

        expect(testRunner.getPreviousRequests()[2].hostname).toContain("unvalidated-test.com");
        expect(testRunner.getPreviousRequests()[2].hostname).toContain("http://");
        expect(testRunner.getPreviousRequests()[2].hostname.length).toEqual(64);
    })

})