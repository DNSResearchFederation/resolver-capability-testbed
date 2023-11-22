import NSECHostQueryTestRunner from "../../src/test-type-runner/nsec-host-query-test-runner";
describe("Test nsec host query test runner", function ()  {

    it("Should be able to make 2 request to the correct domains", () => {

        let testRunner = new NSECHostQueryTestRunner();
        testRunner.runTest("test.com", []);

        expect(testRunner.getPreviousRequests().length).toEqual(3);
        expect(testRunner.getPreviousRequests()[0].hostname).toEqual("https://apples.test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toEqual("https://pears.test.com");
        expect(testRunner.getPreviousRequests()[2].hostname).toEqual("https://oranges.test.com");

    })

    it("Should be able perform insecure requests", () => {

        let testRunner = new NSECHostQueryTestRunner();
        testRunner.runTest("test.com", {"insecure": true});

        expect(testRunner.getPreviousRequests().length).toEqual(3);
        expect(testRunner.getPreviousRequests()[0].hostname).toEqual("http://apples.test.com");
        expect(testRunner.getPreviousRequests()[1].hostname).toEqual("http://pears.test.com");
        expect(testRunner.getPreviousRequests()[2].hostname).toEqual("http://oranges.test.com");
    })

})