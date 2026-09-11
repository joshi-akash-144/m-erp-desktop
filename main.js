const { app, BrowserWindow, ipcMain, dialog, shell } = require("electron");
const path = require("path");
const fs = require("fs");
const os = require("os");
const { spawn } = require("child_process");
const http = require("http");
const { autoUpdater } = require("electron-updater");


let currentInternetStatus = true;
let internetCheckTimer = null;

let failedInternetChecks = 0;
let successfulInternetChecks = 0;

let mysqlProcess = null;
let laravelProcess = null;
let mainWindow = null;

let updateCheckInProgress = false;
let updateDownloaded = false;
let updateDownloading = false;

let mysqlPath = "";
let mysqlConfigPath = "";
let phpPath = "";
let laravelPath = "";

let laravelStoragePath = "";

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

    // console.log("Internet checks:", results);

    // Internet is considered available if ANY one works
    return results.some((result) => result === true);
}

// function sendInternetStatus() {
//     if (mainWindow && !mainWindow.isDestroyed()) {
//         mainWindow.webContents.send(
//             "internet-status",
//             currentInternetStatus
//         );
//     }
// }

async function updateInternetStatus() {

    const online = await checkInternetConnection();

    // console.log(
    //     "Internet check result:",
    //     online ? "CONNECTED" : "DISCONNECTED"
    // );

    if (online) {

        successfulInternetChecks++;
        failedInternetChecks = 0;

        // Change to connected after 1 successful check
        if (!currentInternetStatus) {
            currentInternetStatus = true;
            // sendInternetStatus();
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
            // sendInternetStatus();
        }
    }
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

    // console.log(
    //     "Internet:",
    //     online ? "CONNECTED" : "DISCONNECTED"
    // );

    if (online !== lastInternetStatus) {
        sendInternetStatus(online);
        lastInternetStatus = online;
    }
}, 3000);

function createDatabase() {
    return new Promise((resolve, reject) => {

        const mysqlClientPath = path.join(
            path.dirname(mysqlPath),
            "mysql.exe"
        );

        const process = spawn(
            mysqlClientPath,
            [
                "-h", "127.0.0.1",
                "-P", "3307",
                "-u", "root",
                "-e",
                "CREATE DATABASE IF NOT EXISTS m_erp_desktop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
            ],
            {
                cwd: path.dirname(mysqlClientPath),
                windowsHide: true
            }
        );

        let output = "";

        process.stdout.on("data", data => {
            console.log(
                "Create DB:",
                data.toString()
            );
        });

        process.stderr.on("data", data => {
            output += data.toString();

            console.error(
                "Create DB Error:",
                data.toString()
            );
        });

        process.on("close", code => {

            if (code === 0) {
                console.log(
                    "m_erp_desktop database created."
                );

                resolve();
            } else {
                reject(
                    new Error(
                        "Database creation failed:\n" +
                        output
                    )
                );
            }
        });
    });
}

// function importDatabaseTemplate() {
//     return new Promise((resolve, reject) => {

//         const mysqlClientPath = path.join(
//             path.dirname(mysqlPath),
//             "mysql.exe"
//         );

//         const templatePath = app.isPackaged
//             ? path.join(
//                 process.resourcesPath,
//                 "database-template",
//                 "m_erp_desktop.sql"
//             )
//             : path.join(
//                 __dirname,
//                 "database-template",
//                 "m_erp_desktop.sql"
//             );

//         console.log(
//             "Importing database template:",
//             templatePath
//         );

//         const mysqlProcess = spawn(
//             mysqlClientPath,
//             [
//                 "-h", "127.0.0.1",
//                 "-P", "3307",
//                 "-u", "root",
//                 "m_erp_desktop"
//             ],
//             {
//                 cwd: path.dirname(mysqlClientPath),
//                 windowsHide: true,
//                 stdio: ["pipe", "pipe", "pipe"]
//             }
//         );

//         mysqlProcess.stdout.on("data", data => {
//             console.log(
//                 "Database import:",
//                 data.toString()
//             );
//         });

//         let errorOutput = "";

//         mysqlProcess.stderr.on("data", data => {
//             errorOutput += data.toString();

//             console.error(
//                 "Database import error:",
//                 data.toString()
//             );
//         });

//         const sqlStream = fs.createReadStream(
//             templatePath
//         );

//         sqlStream.on("error", error => {
//             reject(error);
//         });

//         sqlStream.pipe(
//             mysqlProcess.stdin
//         );

//         mysqlProcess.on("close", code => {

//             if (code === 0) {

//                 console.log(
//                     "Database template imported successfully."
//                 );

//                 resolve();

//             } else {

//                 reject(
//                     new Error(
//                         "Database import failed:\n" +
//                         errorOutput
//                     )
//                 );
//             }
//         });
//     });
// }

function importDatabaseTemplate() {
    return new Promise((resolve, reject) => {

        const mysqlClientPath = path.join(
            path.dirname(mysqlPath),
            "mysql.exe"
        );

        const templatePath = app.isPackaged
            ? path.join(
                process.resourcesPath,
                "database-template",
                "m_erp_desktop.sql"
            )
            : path.join(
                __dirname,
                "database-template",
                "m_erp_desktop.sql"
            );

        console.log(
            "MySQL Client:",
            mysqlClientPath
        );

        console.log(
            "SQL Template:",
            templatePath
        );

        // Check mysql.exe
        if (!fs.existsSync(mysqlClientPath)) {
            reject(
                new Error(
                    "mysql.exe not found:\n" +
                    mysqlClientPath
                )
            );
            return;
        }

        // Check SQL file
        if (!fs.existsSync(templatePath)) {
            reject(
                new Error(
                    "Database template not found:\n" +
                    templatePath
                )
            );
            return;
        }

        const stats = fs.statSync(templatePath);

        console.log(
            "SQL file size:",
            stats.size,
            "bytes"
        );

        console.log(
            "Importing database template..."
        );

        const mysqlProcess = spawn(
            mysqlClientPath,
            [
                "-h", "127.0.0.1",
                "-P", "3307",
                "-u", "root",
                "m_erp_desktop"
            ],
            {
                cwd: path.dirname(mysqlClientPath),
                windowsHide: true,
                stdio: ["pipe", "pipe", "pipe"]
            }
        );

        console.log(
            "mysql.exe started. PID:",
            mysqlProcess.pid
        );

        let errorOutput = "";

        mysqlProcess.stdout.on("data", data => {

            console.log(
                "Database import:",
                data.toString()
            );

        });

        mysqlProcess.stderr.on("data", data => {

            const message = data.toString();

            errorOutput += message;

            console.error(
                "Database import error:",
                message
            );

        });

        mysqlProcess.on("error", error => {

            console.error(
                "mysql.exe process error:",
                error
            );

            reject(error);

        });

        const sqlStream = fs.createReadStream(
            templatePath
        );

        sqlStream.on("error", error => {

            console.error(
                "SQL file read error:",
                error
            );

            mysqlProcess.kill();

            reject(error);

        });

        sqlStream.on("open", () => {

            console.log(
                "SQL file opened. Starting import..."
            );

        });

        sqlStream.on("end", () => {

            console.log(
                "SQL file sent to mysql.exe."
            );

        });

        sqlStream.pipe(
            mysqlProcess.stdin
        );

        mysqlProcess.stdin.on("error", error => {

            console.error(
                "MySQL stdin error:",
                error
            );

        });

        mysqlProcess.on("close", code => {

            console.log(
                "mysql.exe closed. Exit code:",
                code
            );

            if (code === 0) {

                console.log(
                    "Database template imported successfully."
                );

                resolve();

            } else {

                reject(
                    new Error(
                        "Database import failed.\n" +
                        "Exit code: " +
                        code +
                        "\n" +
                        errorOutput
                    )
                );

            }

        });

    });
}

function initializeMySQLData(mysqlDataPath) {
    return new Promise((resolve, reject) => {
        try {
            console.log("Checking MariaDB data directory...");

            // Create data directory if it does not exist
            if (!fs.existsSync(mysqlDataPath)) {
                console.log(
                    "MariaDB data directory does not exist. Creating..."
                );

                fs.mkdirSync(mysqlDataPath, {
                    recursive: true
                });
            }

            // MariaDB system database
            const mysqlSystemDatabase = path.join(
                mysqlDataPath,
                "mysql"
            );

            // If mysql system database already exists,
            // MariaDB has already been initialized.
            if (fs.existsSync(mysqlSystemDatabase)) {
                console.log(
                    "MariaDB data directory already initialized."
                );

                resolve();
                return;
            }

            console.log(
                "MariaDB data directory is not initialized."
            );

            console.log(
                "Initializing MariaDB system tables..."
            );

            const installDbPath = path.join(
                path.dirname(mysqlPath),
                "mysql_install_db.exe"
            );

            if (!fs.existsSync(installDbPath)) {
                reject(
                    new Error(
                        "mysql_install_db.exe not found:\n" +
                        installDbPath
                    )
                );
                return;
            }

            console.log(
                "MariaDB installer:",
                installDbPath
            );

            const initProcess = spawn(
                installDbPath,
                [
                    "--datadir=" + mysqlDataPath,
                    "--port=3307"
                ],
                {
                    cwd: path.dirname(installDbPath),
                    windowsHide: true
                }
            );

            let output = "";

            initProcess.stdout.on("data", (data) => {
                const text = data.toString();

                output += text;

                console.log(
                    "MariaDB installation:",
                    text
                );
            });

            initProcess.stderr.on("data", (data) => {
                const text = data.toString();

                output += text;

                console.error(
                    "MariaDB installation:",
                    text
                );
            });

            initProcess.on("error", (error) => {
                reject(error);
            });

            initProcess.on("close", (code) => {

                console.log(
                    "mysql_install_db exited with code:",
                    code
                );

                if (
                    code === 0 &&
                    fs.existsSync(mysqlSystemDatabase)
                ) {
                    console.log(
                        "MariaDB system tables initialized successfully."
                    );

                    resolve();

                } else {
                    reject(
                        new Error(
                            "MariaDB system table initialization failed.\n" +
                            "Exit code: " +
                            code +
                            "\n\n" +
                            output
                        )
                    );
                }
            });

        } catch (error) {
            reject(error);
        }
    });
}

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
                "--basedir=" + path.dirname(path.dirname(mysqlPath)),
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

        console.log(
            "Electron Laravel Storage:",
            laravelStoragePath
        );

        const laravelEnv = {
            ...process.env,
            LARAVEL_STORAGE_PATH: laravelStoragePath
        };

        console.log(
            "Environment LARAVEL_STORAGE_PATH:",
            laravelEnv.LARAVEL_STORAGE_PATH
        );

        laravelProcess = spawn(
            phpPath,
            [
                "artisan",
                "serve",
                "--host=127.0.0.1",
                "--port=8000"
            ],
            {
                cwd: laravelPath,
                env: laravelEnv,
                windowsHide: true,
                stdio: ["ignore", "pipe", "pipe"]
            }
        );

        let serverStarted = false;

        laravelProcess.stdout.on("data", (data) => {
            const output = data.toString();

            console.log("Laravel:", output);

            if (
                output.includes("Server running on") &&
                !serverStarted
            ) {
                serverStarted = true;
                console.log("Laravel server process started.");
                resolve();
            }
        });

        laravelProcess.stderr.on("data", (data) => {
            console.error(
                "Laravel Error:",
                data.toString()
            );
        });

        laravelProcess.on("error", (error) => {
            console.error(
                "Laravel process error:",
                error
            );

            if (!serverStarted) {
                reject(error);
            }
        });

        laravelProcess.on("close", (code) => {
            console.log(
                "Laravel stopped with code:",
                code
            );

            if (!serverStarted) {
                reject(
                    new Error(
                        "Laravel process stopped before server started. Exit code: " +
                        code
                    )
                );
            }
        });
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

// function waitForLaravel(LARAVEL_URL ,timeout = 30000) {
//     return new Promise((resolve, reject) => {
//         const start = Date.now();

//         const check = () => {
//             const request = http.get(LARAVEL_URL, (response) => {
//                 response.destroy();

//                 console.log("Laravel is ready!");
//                 resolve();
//             });

//             request.on("error", () => {
//                 if (Date.now() - start >= timeout) {
//                     reject(
//                         new Error(
//                             "Laravel did not start within 30 seconds."
//                         )
//                     );
//                     return;
//                 }

//                 setTimeout(check, 500);
//             });

//             request.setTimeout(1000, () => {
//                 request.destroy();
//             });
//         };

//         check();
//     });
// }

// function waitForLaravel(LARAVEL_URL, timeout = 30000) {
//     return new Promise((resolve, reject) => {
//         const start = Date.now();

//         const check = () => {
//             const request = http.get(LARAVEL_URL, (response) => {
//                 response.destroy();

//                 console.log("Laravel is ready!");
//                 resolve();
//             });

//             request.on("error", () => {
//                 if (Date.now() - start >= timeout) {
//                     reject(
//                         new Error(
//                             "Laravel did not start within 30 seconds."
//                         )
//                     );
//                     return;
//                 }

//                 setTimeout(check, 300);
//             });

//             request.setTimeout(1000, () => {
//                 request.destroy();
//             });
//         };

//         check();
//     });
// }

function waitForLaravel(LARAVEL_URL, timeout = 30000) {
    return new Promise((resolve, reject) => {
        const start = Date.now();

        const check = () => {
            const request = http.get(LARAVEL_URL, (response) => {
                console.log("Laravel HTTP status:", response.statusCode);

                response.resume();

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

                setTimeout(check, 300);
            });

            request.setTimeout(3000, () => {
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

function prepareLaravelStorage() {
    console.log("=================================");
    console.log("Preparing Laravel writable storage");
    console.log("=================================");

    const sourceStorage = path.join(
        laravelPath,
        "storage"
    );

    if (!fs.existsSync(laravelStoragePath)) {
        console.log("Creating Laravel storage:");
        console.log(laravelStoragePath);

        fs.cpSync(
            sourceStorage,
            laravelStoragePath,
            {
                recursive: true
            }
        );

        console.log("Laravel storage copied successfully.");
    } else {
        console.log("Laravel storage already exists.");
    }

    // Make sure required Laravel folders exist
    const requiredFolders = [
        "app",
        "app/private",
        "app/public",
        "framework",
        "framework/cache",
        "framework/cache/data",
        "framework/sessions",
        "framework/views",
        "logs"
    ];

    for (const folder of requiredFolders) {
        const folderPath = path.join(
            laravelStoragePath,
            folder
        );

        if (!fs.existsSync(folderPath)) {
            fs.mkdirSync(folderPath, {
                recursive: true
            });
        }
    }

    console.log("Laravel writable storage ready.");
}

async function initializeLaravel() {
    console.log("=================================");
    console.log("First-time Laravel initialization");
    console.log("=================================");

    // Create/copy Laravel storage to AppData
    prepareLaravelStorage();

    await runArtisan("storage:link");
    await runArtisan("optimize:clear");

    console.log("Laravel initialization completed.");

}

async function updateLaravelDatabase() {
    console.log("=================================");
    console.log("Checking Laravel database migrations");
    console.log("=================================");

    await runArtisan("migrate", ["--force"]);

    await runArtisan("optimize:clear");

    console.log("Laravel database migration check completed.");
}

// update exe file
function setupAutoUpdater() {

    // Auto-update only works for packaged application
    if (!app.isPackaged) {
        console.log("Auto-update disabled in development mode.");
        return;
    }

    console.log("Auto-update enabled.");

    // IMPORTANT:
    // Update will NOT download automatically.
    // User must click "Download Update".
    autoUpdater.autoDownload = false;

    // If update is downloaded but user chooses "Later",
    // it can be installed when application quits.
    autoUpdater.autoInstallOnAppQuit = true;

    autoUpdater.on("checking-for-update", () => {
        console.log("Checking for application updates...");
        updateCheckInProgress = true;
    });

    autoUpdater.on("update-available", async (info) => {

        console.log("Update available:", info.version);

        updateCheckInProgress = false;

        if (updateDownloading || updateDownloaded) {
            return;
        }

        try {

            const result = await dialog.showMessageBox(mainWindow, {
                type: "info",
                title: "Update Available",
                message: `M ERP version ${info.version} is available.`,
                detail:
                    "A new version of M ERP is available. Would you like to download it now?",
                buttons: [
                    "Download Update",
                    "Later"
                ],
                defaultId: 0,
                cancelId: 1
            });

            if (result.response === 0) {

                console.log("User selected Download Update.");

                updateDownloading = true;

                try {
                    await autoUpdater.downloadUpdate();
                } catch (error) {
                    updateDownloading = false;

                    console.error(
                        "Update download failed:",
                        error
                    );
                }

            } else {

                console.log(
                    "User postponed the update."
                );
            }

        } catch (error) {

            console.error(
                "Update dialog error:",
                error
            );
        }
    });

    autoUpdater.on("update-not-available", () => {

        updateCheckInProgress = false;

        console.log(
            "No update available. Current version:",
            app.getVersion()
        );
    });

    autoUpdater.on("download-progress", (progress) => {

        console.log(
            `Update download: ${progress.percent.toFixed(1)}%`
        );

        if (mainWindow && !mainWindow.isDestroyed()) {

            mainWindow.webContents.send(
                "update-progress",
                {
                    percent: progress.percent,
                    transferred: progress.transferred,
                    total: progress.total
                }
            );
        }
    });

    autoUpdater.on("update-downloaded", async (info) => {

        updateDownloading = false;
        updateDownloaded = true;

        console.log(
            "Update downloaded successfully:",
            info.version
        );

        if (!mainWindow || mainWindow.isDestroyed()) {
            return;
        }

        try {

            const result = await dialog.showMessageBox(
                mainWindow,
                {
                    type: "info",
                    title: "Update Ready",
                    message:
                        `M ERP ${info.version} has been downloaded.`,
                    detail:
                        "Restart the application now to install the update.",
                    buttons: [
                        "Restart Now",
                        "Later"
                    ],
                    defaultId: 0,
                    cancelId: 1
                }
            );

            if (result.response === 0) {

                console.log(
                    "User selected Restart Now."
                );

                autoUpdater.quitAndInstall(
                    false,
                    true
                );

            } else {

                console.log(
                    "User selected Later."
                );

                console.log(
                    "Update will be installed when the application quits."
                );
            }

        } catch (error) {

            console.error(
                "Update installation dialog error:",
                error
            );
        }
    });

    autoUpdater.on("error", (error) => {

        updateCheckInProgress = false;
        updateDownloading = false;

        console.error(
            "Auto-update error:",
            error
        );

        if (
            mainWindow &&
            !mainWindow.isDestroyed()
        ) {

            mainWindow.webContents.send(
                "update-error",
                error.message
            );
        }
    });
}

// check update
function checkForUpdates() {
    if (!app.isPackaged) {
        console.log(
            "Skipping update check because application is not packaged."
        );
        return;
    }

    console.log(
        "Checking GitHub for M ERP updates..."
    );

    autoUpdater.checkForUpdates().catch((error) => {
        console.error(
            "Failed to check for updates:",
            error
        );
    });
}


async function startApplication() {
    try {

        const runtimePath = app.isPackaged
            ? path.join(process.resourcesPath, "runtime")
            : path.join(__dirname, "runtime");

        // Writable Laravel storage outside Program Files
        laravelStoragePath = path.join(
            app.getPath("userData"),
            "LaravelStorage"
        );
        console.log("Laravel Storage:", laravelStoragePath);

        // const mysqlDataPath = path.join(
        //     __dirname,
        //     "data",
        //     "mysql"
        // );

        const mysqlDataPath = app.isPackaged
            ? path.join(
                app.getPath("userData"),
                "data",
                "mysql"
            )
            : path.join(
                __dirname,
                "data",
                "mysql"
        );

        const initializationFile = path.join(
            app.getPath("userData"),
            "initialized"
        );

        // auto create folder
        if (!fs.existsSync(mysqlDataPath)) {
            fs.mkdirSync(mysqlDataPath, { recursive: true });
        }

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

        console.log("=================================");
        console.log("Initialization file:", initializationFile);
        console.log(
            "Already initialized:",
            fs.existsSync(initializationFile)
        );
        console.log("=================================");

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

        await initializeMySQLData(mysqlDataPath);      
        
        // 1. Start MariaDB
        await startMySQL(mysqlDataPath);
        console.log("Database started.");
        

        console.time("INITIALIZATION");

        // your first-installation block

        console.timeEnd("INITIALIZATION");

        
        // 2. First-time Laravel initialization
        // if (!fs.existsSync(initializationFile)) {

        //     console.log("First run detected.");

        //     await initializeLaravel();

        //     fs.writeFileSync(
        //         initializationFile,
        //         new Date().toISOString()
        //     );

        //     console.log("First run completed.");
        // } else {

        //     console.log(
        //         "Laravel already initialized."
        //     );
        // }

    // if (!fs.existsSync(initializationFile)) {

    //     console.log("=================================");
    //     console.log("FIRST INSTALLATION DETECTED");
    //     console.log("=================================");

    //     // 1. Create application database
    //     await createDatabase();

    //     // 2. Import your existing database template
    //     await importDatabaseTemplate();

    //     // 3. Laravel first-time setup
    //     await initializeLaravel();

    //     // 4. Mark installation as completed
    //     fs.writeFileSync(
    //         initializationFile,
    //         new Date().toISOString()
    //     );

    //     console.log("=================================");
    //     console.log("FIRST INSTALLATION COMPLETED");
    //     console.log("=================================");

    // } else {

    //     console.log("=================================");
    //     console.log("EXISTING INSTALLATION DETECTED");
    //     console.log("=================================");

    //     // Only run pending migrations
    //     await updateLaravelDatabase();

    //     console.log("Database update check completed.");
    // }

    if (!fs.existsSync(initializationFile)) {

        console.log("=================================");
        console.log("FIRST INSTALLATION DETECTED");
        console.log("=================================");

        await createDatabase();
        await importDatabaseTemplate();
        await initializeLaravel();

        fs.writeFileSync(
            initializationFile,
            new Date().toISOString()
        );

        console.log("=================================");
        console.log("FIRST INSTALLATION COMPLETED");
        console.log("=================================");

    }else {

        console.log("Existing installation detected.");

        await updateLaravelDatabase();

    }

    console.time("LARAVEL");

        // 3. Start Laravel
        await startLaravel();
        console.log("Laravel started.");

        console.time("WAIT_LARAVEL");


        // 4. Wait until Laravel HTTP server is ready
        await waitForLaravel(LARAVEL_URL);
        // console.log("Laravel HTTP server ready.");

        console.timeEnd("WAIT_LARAVEL");
        console.timeEnd("LARAVEL");

        console.time("WINDOW");


        // 5. Open Electron window
        await createWindow();

        console.timeEnd("WINDOW");

        console.log("M-ERP started.");

        // console.log("M-ERP started.");

    } catch (error) {

        console.error(
            "Application startup failed:",
            error
        );
    }
}

console.log("USER DATA:", app.getPath("userData"));
app.whenReady().then(() => {
   
    // startApplication();
    // app.on("activate", () => {
    //     if (BrowserWindow.getAllWindows().length === 0) createWindow();
    // });

    setupAutoUpdater();

    startApplication();

    app.on("activate", () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });

    // Give Laravel/PHP/MySQL some time to start
    // before checking for updates.
    if (app.isPackaged) {
        setTimeout(() => {
            checkForUpdates();
        }, 10000);
    }
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