const { app, BrowserWindow, ipcMain, dialog, shell } = require("electron");
const path = require("path");
const fs = require("fs");
const os = require("os");
const { spawn } = require("child_process");
const http = require("http");

let currentInternetStatus = true;
let internetCheckTimer = null;

let failedInternetChecks = 0;
let successfulInternetChecks = 0;

let mysqlProcess = null;
let laravelProcess = null;
let mainWindow = null;

let mysqlPath = "";
let mysqlConfigPath = "";
let phpPath = "";
let laravelPath = "";

const preloadPath = path.join(__dirname, "preload.js");
const LARAVEL_URL = "http://127.0.0.1:8000";

const initializationFile = path.join(
    app.getPath("userData"),
    "initialized"
);

const https = require("https");

function checkUrl(url) {
    return new Promise((resolve) => {

        const request = https.get(
            url,
            {
                timeout: 5000,
            },
            (response) => {
                response.resume();

                resolve(
                    response.statusCode >= 200 &&
                    response.statusCode < 500
                );
            }
        );

        request.on("error", () => {
            resolve(false);
        });

        request.on("timeout", () => {
            request.destroy();
            resolve(false);
        });
    });
}



async function checkInternetConnection() {

    const results = await Promise.all([
        checkUrl("https://www.google.com/generate_204"),
        checkUrl("https://www.msftconnecttest.com/connecttest.txt"),
        checkUrl("https://www.cloudflare.com/cdn-cgi/trace"),
    ]);

    console.log("Internet checks:", results);

    // Internet is considered available if ANY one works
    return results.some((result) => result === true);
}

function sendInternetStatus() {
    if (mainWindow && !mainWindow.isDestroyed()) {
        mainWindow.webContents.send(
            "internet-status",
            currentInternetStatus
        );
    }
}

async function updateInternetStatus() {

    const online = await checkInternetConnection();

    console.log(
        "Internet check result:",
        online ? "CONNECTED" : "DISCONNECTED"
    );

    if (online) {

        successfulInternetChecks++;
        failedInternetChecks = 0;

        // Change to connected after 1 successful check
        if (!currentInternetStatus) {
            currentInternetStatus = true;
            sendInternetStatus();
        }

    } else {

        failedInternetChecks++;
        successfulInternetChecks = 0;

        // Require 2 consecutive failures
        if (
            currentInternetStatus &&
            failedInternetChecks >= 2
        ) {
            currentInternetStatus = false;
            sendInternetStatus();
        }
    }
}

function startInternetMonitoring() {

    updateInternetStatus();

    internetCheckTimer = setInterval(() => {
        updateInternetStatus();
    }, 10000);
}

ipcMain.handle("get-internet-status", () => {
    return currentInternetStatus;
});

// Show notification on application startup
async function checkInternetAndNotify() {
    const online = await checkInternetConnection();

    lastInternetStatus = online;

    sendInternetStatus(online);
}

function sendInternetStatus(online) {
    if (
        mainWindow &&
        !mainWindow.isDestroyed()
    ) {
        mainWindow.webContents.send(
            "internet-status",
            online
        );
    }
}


// Monitor internet connection
let lastInternetStatus = true;

setInterval(async () => {
    const online = await checkInternetConnection();

    console.log(
        "Internet:",
        online ? "CONNECTED" : "DISCONNECTED"
    );

    if (online !== lastInternetStatus) {
        sendInternetStatus(online);
        lastInternetStatus = online;
    }
}, 30000);


function startMySQL(mysqlDataPath) {
    return new Promise((resolve, reject) => {
        console.log("Starting MariaDB...");
        let resolved = false;

        const markReady = (data) => {
            const output = data.toString();
            console.log("MariaDB: " + output);

            if (output.includes("ready for connections") && !resolved) {
                resolved = true;
                console.log("MariaDB is ready!");
                resolve();
            }
        };

        mysqlProcess = spawn(
            mysqlPath,
            [
                "--defaults-file=" + mysqlConfigPath,
                "--datadir=" + mysqlDataPath,
                "--console"
            ],
            {
                cwd: path.dirname(mysqlPath),
                windowsHide: true
            }
        );

        mysqlProcess.stdout.on("data", markReady);
        mysqlProcess.stderr.on("data", markReady);

        mysqlProcess.on("error", (error) => {
            if (!resolved) reject(error);
        });

        mysqlProcess.on("close", (code) => {
            console.log("MariaDB stopped with code " + code);

            if (!resolved && code !== 0) {
                reject(
                    new Error(
                        "MariaDB stopped before becoming ready. Code: " + code
                    )
                );
            }
        });

        setTimeout(() => {
            if (!resolved) {
                reject(
                    new Error(
                        "MariaDB did not start within 30 seconds."
                    )
                );
            }
        }, 30000);
    });
}

function startLaravel() {
    return new Promise((resolve, reject) => {
        console.log("Starting Laravel...");

        laravelProcess = spawn(
            phpPath,
            ["artisan", "serve", "--host=127.0.0.1", "--port=8000"],
            { cwd: laravelPath, windowsHide: true }
        );

        laravelProcess.stdout.on("data", (data) => {
            const output = data.toString();
            console.log("Laravel: " + output);
            if (output.includes("Development Server")) {
                console.log("Laravel server started!");
                resolve();
            }
        });

        laravelProcess.stderr.on("data", (data) => console.error("Laravel Error: " + data));
        laravelProcess.on("error", reject);
        laravelProcess.on("close", (code) => console.log("Laravel stopped with code " + code));

        setTimeout(() => resolve(), 5000);
    });
}

function setupDownloadHandler(win) {
    win.webContents.session.on("will-download", (event, item) => {
        const defaultName = item.getFilename();
        const ext = path.extname(defaultName).toLowerCase();

        const filters = [];
        if (ext === ".pdf")   filters.push({ name: "PDF Files",   extensions: ["pdf"] });
        else if (ext === ".xlsx" || ext === ".xls") filters.push({ name: "Excel Files", extensions: ["xlsx", "xls"] });
        else if (ext === ".csv") filters.push({ name: "CSV Files", extensions: ["csv"] });
        filters.push({ name: "All Files", extensions: ["*"] });

        const savePath = dialog.showSaveDialogSync(win, {
            title: "Save File",
            defaultPath: path.join(app.getPath("downloads"), defaultName),
            filters: filters
        });

        if (savePath) {
            item.setSavePath(savePath);
            item.on("done", (event, state) => {
                console.log("Download " + state + ": " + savePath);
            });
        } else {
            item.cancel();
        }
    });
}


async function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        webPreferences: {
            preload: preloadPath,
            nodeIntegration: false,
            contextIsolation: true,
            sandbox: false,          
        }
    });

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {

        console.log("Opening new window:", url);

        return {
            action: "allow"
        };

    });

        // Detect window.open() windows
    mainWindow.webContents.on("did-create-window", (childWindow) => {

        console.log("PRINT WINDOW CREATED");

        childWindow.webContents.once("did-finish-load", () => {

            console.log("Print window loaded");

            setTimeout(() => {

                childWindow.webContents.print(
                    {
                        silent: false,
                        printBackground: true
                    },
                    (success, failureReason) => {

                        console.log(
                            "Print result:",
                            success
                        );

                        console.log(
                            "Failure reason:",
                            failureReason
                        );

                    }
                );

            }, 500);

        });

    });

    setupDownloadHandler(mainWindow);

    await mainWindow.loadURL(LARAVEL_URL);    

    const online = await checkInternetConnection();

    sendInternetStatus(online);

    lastInternetStatus = online;
}

// async function startApplication() {
//     try {
//         const runtimePath = app.isPackaged
//             ? path.join(process.resourcesPath, "runtime")
//             : path.join(__dirname, "runtime");

//         const mysqlDataPath = app.isPackaged
//             ? path.join(process.resourcesPath, "data", "mysql")
//             : path.join(__dirname, "data", "mysql");


//         mysqlPath       = path.join(runtimePath, "mysql", "bin", "mysqld.exe");
//         mysqlConfigPath = path.join(runtimePath, "mysql", "my.ini");
//         phpPath         = path.join(runtimePath, "php", "php.exe");
//         laravelPath     = path.join(runtimePath, "M-ERP-Desktop");

//         await startMySQL();
//         console.log("Database started.");

//         await startLaravel();
//         console.log("Laravel started.");

//         await createWindow();
//         console.log("M-ERP started.");

//     } catch (error) {
//         console.error("Application startup failed:", error);
//     }
// }

function waitForLaravel(LARAVEL_URL ,timeout = 30000) {
    return new Promise((resolve, reject) => {
        const start = Date.now();

        const check = () => {
            const request = http.get(LARAVEL_URL, (response) => {
                response.destroy();

                console.log("Laravel is ready!");
                resolve();
            });

            request.on("error", () => {
                if (Date.now() - start >= timeout) {
                    reject(
                        new Error(
                            "Laravel did not start within 30 seconds."
                        )
                    );
                    return;
                }

                setTimeout(check, 500);
            });

            request.setTimeout(1000, () => {
                request.destroy();
            });
        };

        check();
    });
}

function runArtisan(command, args = []) {
    return new Promise((resolve, reject) => {

        console.log(
            `Running: php artisan ${command} ${args.join(" ")}`
        );

        const artisanProcess = spawn(
            phpPath,
            [
                "artisan",
                command,
                ...args
            ],
            {
                cwd: laravelPath,
                windowsHide: true
            }
        );

        artisanProcess.stdout.on("data", (data) => {
            console.log(
                "Artisan:",
                data.toString()
            );
        });

        artisanProcess.stderr.on("data", (data) => {
            console.error(
                "Artisan Error:",
                data.toString()
            );
        });

        artisanProcess.on("error", (error) => {
            reject(error);
        });

        artisanProcess.on("close", (code) => {

            console.log(
                `php artisan ${command} exited with code ${code}`
            );

            if (code === 0) {
                resolve();
            } else {
                reject(
                    new Error(
                        `php artisan ${command} failed with code ${code}`
                    )
                );
            }
        });
    });
}

// async function initializeLaravel() {
//     console.log("=================================");
//     console.log("FIRST INSTALL - INITIALIZING");
//     console.log("=================================");

//     // Clear database and recreate all tables + seed data
//     await runArtisan("migrate:fresh", ["--seed", "--force"]);

//     // Create public/storage symbolic link
//     await runArtisan("storage:link");

//     // Clear Laravel caches
//     await runArtisan("optimize:clear");

//     console.log("=================================");
//     console.log("FIRST INSTALL COMPLETED");
//     console.log("=================================");
// }

async function initializeLaravel() {
    console.log("=================================");
    console.log("First-time Laravel initialization");
    console.log("=================================");

    await runArtisan("migrate", ["--force"]);
    await runArtisan("db:seed", ["--force"]);
    await runArtisan("storage:link");
    await runArtisan("optimize:clear");

    console.log("Laravel initialization completed.");
}




async function startApplication() {
    try {

        const runtimePath = app.isPackaged
            ? path.join(process.resourcesPath, "runtime")
            : path.join(__dirname, "runtime");

        const mysqlDataPath = path.join(
            __dirname,
            "data",
            "mysql"
        );

        mysqlPath = path.join(
            runtimePath,
            "mysql",
            "bin",
            "mysqld.exe"
        );

        mysqlConfigPath = path.join(
            runtimePath,
            "mysql",
            "my.ini"
        );

        phpPath = path.join(
            runtimePath,
            "php",
            "php.exe"
        );

        laravelPath = path.join(
            runtimePath,
            "Laravel"
        );

        // const initializationFile = path.join(
        //     app.getPath("userData"),
        //     "initialized"
        // );

        // console.log("=================================");
        // console.log("Initialization file:", initializationFile);
        // console.log(
        //     "Already initialized:",
        //     fs.existsSync(initializationFile)
        // );
        // console.log("=================================");


        console.log("MySQL Path:", mysqlPath);
        console.log("MySQL Config:", mysqlConfigPath);
        console.log("MySQL Data:", mysqlDataPath);
        console.log("PHP Path:", phpPath);
        console.log("Laravel Path:", laravelPath);

        // Check internet
        await checkInternetAndNotify();

        // 1. Start MariaDB
        await startMySQL(mysqlDataPath);
        console.log("Database started.");

        // 2. First-time Laravel initialization
        if (!fs.existsSync(initializationFile)) {

            console.log("First run detected.");

            await initializeLaravel();

            fs.writeFileSync(
                initializationFile,
                new Date().toISOString()
            );

            console.log("First run completed.");
        } else {

            console.log(
                "Laravel already initialized."
            );
        }

        // 3. Start Laravel
        await startLaravel();
        console.log("Laravel started.");

        // 4. Wait until Laravel HTTP server is ready
        await waitForLaravel(LARAVEL_URL);
        console.log("Laravel HTTP server ready.");

        // 5. Open Electron window
        await createWindow();

        // Start internet monitoring AFTER UI exists
        startInternetMonitoring();

        console.log("M-ERP started.");

    } catch (error) {

        console.error(
            "Application startup failed:",
            error
        );
    }
}


// async function startApplication() {
//     try {
//         const runtimePath = app.isPackaged
//             ? path.join(process.resourcesPath, "runtime")
//             : path.join(__dirname, "runtime");

//         const mysqlDataPath = path.join(__dirname, "data", "mysql");

//         mysqlPath       = path.join(runtimePath, "mysql", "bin", "mysqld.exe");
//         mysqlConfigPath = path.join(runtimePath, "mysql", "my.ini");
//         phpPath         = path.join(runtimePath, "php", "php.exe");
//         laravelPath     = path.join(runtimePath, "Laravel");

//         console.log("MySQL Path:", mysqlPath);
//         console.log("MySQL Config:", mysqlConfigPath);
//         console.log("MySQL Data:", mysqlDataPath);

//         await startMySQL(mysqlDataPath);
//         console.log("Database started.");

//         await startLaravel();
//         console.log("Laravel started.");

//         await waitForLaravel(LARAVEL_URL);

//         await createWindow();
//         console.log("M-ERP started.");

//     } catch (error) {
//         console.error("Application startup failed:", error);
//     }
// }



app.whenReady().then(() => {
    startInternetMonitoring();

    startApplication();
    app.on("activate", () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on("window-all-closed", () => {
    if (laravelProcess) { laravelProcess.kill(); laravelProcess = null; }
    if (mysqlProcess)   { mysqlProcess.kill();   mysqlProcess = null;   }
    if (process.platform !== "darwin") app.quit();
});

app.on("before-quit", () => {
    if (laravelProcess) laravelProcess.kill();
    if (mysqlProcess)   mysqlProcess.kill();
});

ipcMain.handle('generate-pdf-from-html', async (event, htmlContent, defaultName = 'report.pdf') => {
    // 1. Shadow window create karein
    let pdfWorkerWin = new BrowserWindow({
        show: false,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    });

    // 2. Data load hone ka event handle karein
    await pdfWorkerWin.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(htmlContent)}`);

    // LARGE DATA FIX: Ensure Karein ki DOM completely load aur render ho chuka hai
    await pdfWorkerWin.webContents.executeJavaScript(`
        new Promise((resolve) => {
            if (document.readyState === 'complete') {
                resolve();
            } else {
                window.addEventListener('load', resolve);
            }
        });
    `);

    // 3. Render complete hone ke baad PDF generate karein
    const pdfData = await pdfWorkerWin.webContents.printToPDF({
        printBackground: true,
        pageSize: 'A4',
        landscape: false,
        preferCSSPageSize: true
    });

    // Hidden window close karein
    pdfWorkerWin.close();

    // 4. Save dialog box open karein
    const { filePath, canceled } = await dialog.showSaveDialog({
        title: 'Save PDF',
        defaultPath: path.join(app.getPath('downloads'), defaultName),
        filters: [{ name: 'PDF Files', extensions: ['pdf'] }]
    });

    if (!canceled && filePath) {
        // 5. Large Buffer file synchronous write karein
        fs.writeFileSync(filePath, pdfData);

        // 6. Large Data File Lock Release hone ke liye 500ms delay rakhein
        setTimeout(() => {
            shell.openPath(filePath);
        }, 500);

        return { success: true, filePath };
    }

    return { success: false, canceled: true };
});