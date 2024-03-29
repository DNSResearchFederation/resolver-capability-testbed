import SingleHostQueryTestRunner from "../../src/test-type-runner/single-host-query-test-runner";

describe("Test type runner tests", function () {

    it("Should be able to request a domain name", async () => {

        let testTypeRunner = new SingleHostQueryTestRunner();
        let response = await testTypeRunner.request("www.dnsrf.org");
        expect(response).toEqual("OK");

    });

    it("Should be able to wait a millisecond value", async () => {

        let testTypeRunner = new SingleHostQueryTestRunner();
        let time = Date.now();
        await testTypeRunner.wait(200);
        expect(Date.now() - time).toBeGreaterThanOrEqual(200);

    });

    it("Should generate UUID", () => {

        let testTypeRunner = new SingleHostQueryTestRunner();
        let uuid = testTypeRunner.getUUID();
        expect(uuid.length).toEqual(36);

    });

    it("Should be able to add a suffix to a domain", () => {

        let testTypeRunner = new SingleHostQueryTestRunner();
        expect(testTypeRunner.addDomainPrefix("test.com", "second-")).toEqual("second-test.com");

    })

});