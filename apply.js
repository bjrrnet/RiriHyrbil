import { execSync } from "node:child_process";
import { readdirSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";
import dotenv from "dotenv";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

dotenv.config({ path: join(__dirname, "config.env") });
const DB = process.env.DB;
if (!DB) {
  console.error("Fel: DB saknas i config.env");
  process.exit(1);
}

const migDir = join(__dirname, "../migrations");

function run(cmd) {
  console.log(`> ${cmd}`);
  execSync(cmd, { stdio: "inherit", shell: true });
}

console.log(`Migrerar '${DB}'...`);

for (const file of readdirSync(migDir)) {
  if (file.endsWith(".sql")) {
    const full = join(migDir, file);
    console.log("Kör", full);
    run(`mariadb ${DB} < "${full}"`);
  }
}

console.log("\nAlla migrationer körda.");

