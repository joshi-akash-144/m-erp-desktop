const e = require("electron");
console.log("TYPE:", typeof e);
console.log("KEYS:", Object.keys(e || {}).slice(0, 15));
console.log("app:", typeof e.app);
console.log("ipcMain:", typeof e.ipcMain);
process.exit(0);
