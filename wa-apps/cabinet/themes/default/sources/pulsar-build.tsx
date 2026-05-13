import { build } from '@scompiler/0003-product';
import { buildConfig } from "./pulsar.config";
import fs from "fs";
import path from "path";

const outputDir = path.join(__dirname, 'dist');

if (fs.existsSync(outputDir)) {
    fs.rmdirSync(outputDir, {recursive: true});
}

build({ ...buildConfig('build'), distDir: outputDir }, fs).then();
