import Ipv6HostQueryTestRunner from "../../src/test-type-runner/ipv6-host-query-test-runner";

describe("Test ipv6 host query test runner", function ()  {

    it("Should be able to make 2 request to the correct domains", () => {

        let testRunner = new Ipv6HostQueryTestRunner();
        testRunner.runTest("test.com", []);

        expect(testRunner.getPreviousRequests().length).toEqual(2);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(53);

        expect(testRunner.getPreviousRequests()[1].hostname).toContain("ipv4-test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("https://");
        expect(testRunner.getPreviousRequests()[1].hostname.length).toEqual(58);

    })

    it("Should be able perform insecure requests", () => {

        let testRunner = new Ipv6HostQueryTestRunner();
        testRunner.runTest("test.com", {"insecure": true});

        expect(testRunner.getPreviousRequests().length).toEqual(2);
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("test.com");
        expect(testRunner.getPreviousRequests()[0].hostname).toContain("http://");
        expect(testRunner.getPreviousRequests()[0].hostname.length).toEqual(52);

        expect(testRunner.getPreviousRequests()[1].hostname).toContain("ipv4-test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toContain("http://");
        expect(testRunner.getPreviousRequests()[1].hostname.length).toEqual(57);

    })

})