import {v4 as uuidv4} from 'uuid';

export default abstract class TestTypeRunner {

    /**
     * Array of requests made
     *
     * @private
     */
    private _requests: any[] = [];

    /**
     * Request a domain name, return the response
     *
     * @param hostname
     * @return Promise<Response>
     */
    public async request(hostname: string): Promise<any> {

        let request: any = {
            "hostname": hostname,
            "time": Date.now()
        }

        // Push request onto stack
        this._requests.push(request);

        if (typeof window !== 'undefined') {
            try {
                let response = await window.fetch(hostname);
                request.status = "sent";
                request.response = {
                    status: response.status,
                    body: await response.text()
                }
                return response;
            } catch (e) {
                request.status = "failed";
                request.errorMessage = e.toString()
            }

        } else {
            return "OK";
        }
    }


    /**
     * Wait a millisecond value
     *
     * @param ms
     */
    async wait(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    /**
     * Get a unique identifier
     *
     * @return string
     */
    getUUID() {
        return uuidv4();
    }

    /**
     * Get previous requests
     */
    getPreviousRequests(): any[] {
        return this._requests;
    }

    /**
     * Run a test
     *
     * @param domainName
     * @param additionalConfig
     */
    public abstract runTest(domainName, additionalConfig?: any, testRunCallback?: any);

}

