const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("electronAPI", {

    // 1. PDF Generation Handler (Matching main.js & Frontend)
    generatePdfFromHtml: async (html, defaultName = 'report.pdf') => {
        console.log("preload: generatePdfFromHtml called");
        console.log("preload: HTML length:", html ? html.length : 0);

        try {
            const result = await ipcRenderer.invoke(
                "generate-pdf-from-html",
                html,
                defaultName
            );

            console.log("preload: PDF generation result:", result);
            return result;

        } catch (error) {
            console.error("preload: PDF generation error:", error);
            return {
                success: false,
                error: error.message
            };
        }
    },

    // 2. Direct HTML Print Handler (Agar aap instant print dialog use karna chahein)
    printHTML: async (html) => {
        console.log("preload: printHTML called");
        try {
            const result = await ipcRenderer.invoke("print-html", html);
            return result;
        } catch (error) {
            console.error("preload: print error:", error);
            return { success: false, error: error.message };
        }
    },
    
    getInternetStatus: async () => {
        return await ipcRenderer.invoke("get-internet-status");
    },

        onInternetStatus: (callback) => {

            ipcRenderer.on(
                "internet-status",
                (event, online) => {

                    console.log(
                        "preload received:",
                        online ? "CONNECTED" : "DISCONNECTED"
                    );

                    callback(online);
                }
            );
        }

  
});