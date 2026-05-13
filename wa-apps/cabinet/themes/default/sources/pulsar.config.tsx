import React, { ReactNode } from "react";
import fs from "fs";
import { Config, svgToReactComponent } from "@scompiler/0003-product/.scompiler/server";
import * as octicons from '@primer/octicons';
import * as feather from 'feather-icons';
import path from "path";
import { File, renameFileBasename } from "@scompiler/0003-product";
import { from, map, merge, mergeMap, Observable, of, tap } from "rxjs";
import rtlcss from "rtlcss";
import postcss from "postcss";
import autoprefixer from "autoprefixer";
import cssnano from "cssnano";
import prettier from "prettier";
import { stubImage } from "@scompiler/stub-image";

const inlineSvg = false;
const iconModes = ['svgInline', 'svgSprite'];

function getBootstrapIcon(iconName: string): ReactNode {
    const mr = /^bootstrap\/(.+)$/.exec(iconName);

    if (!mr) {
        return null;
    }

    const bootstrap = mr[1];

    if (!inlineSvg) {
        return (
            <svg width="1em" height="1em">
                <use xlinkHref={`vendor/bootstrap-icons/bootstrap-icons.svg#${bootstrap}`} />
            </svg>
        );
    }

    const bootstrapPath = 'node_modules/bootstrap-icons/icons/'+bootstrap+'.svg';

    if (!fs.existsSync(bootstrapPath)) {
        return null;
    }

    return svgToReactComponent(fs.readFileSync(bootstrapPath).toString(), bootstrapPath);
}

function getFeatherIcon(iconName: string): ReactNode {
    let mr = /^feather\/(.+)$/.exec(iconName);

    if (!mr) {
        return null;
    }

    const featherIcon = mr[1];

    for (let iconMode of iconModes) {
        if (iconMode === 'svgSprite') {
            return (
                <svg
                    width="1em"
                    height="1em"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <use xlinkHref={`vendor/feather-icons/feather-sprite.svg#${featherIcon}`} />
                </svg>
            );
        }
        if (iconMode === 'svgInline') {
            return svgToReactComponent(feather.icons[featherIcon].toSvg({width: '1em', height: '1em'}), iconName)
        }
        if (iconMode === 'svgReplace') {
            return <i data-feather={featherIcon} />;
        }
    }

    return 'There is no suitable display mode for the requested icon';
}

function getFontAwesomeIcon(iconName: string): ReactNode {
    const mr = /^fontawesome\/(fa[brs] .+)$/.exec(iconName);

    if (!mr) {
        return null;
    }

    return <i className={mr[1]} />;

    // const [collectionKey, name] = mr[1].split(' ');
    // const collectionName = {
    //     fab: 'brands',
    //     far: 'regular',
    //     fas: 'solid',
    // }[collectionKey];
    //
    // if (!inlineSvg) {
    //     return (
    //         <svg
    //             width="1em"
    //             height="1em"
    //         >
    //             <use xlinkHref={`vendor/fontawesome/sprites/${collectionName}.svg#${name.substr(3)}`} />
    //         </svg>
    //     );
    // }
    //
    // const path = `node_modules/@fortawesome/fontawesome-free/svgs/${collectionName}/${name.substr(3)}.svg`;
    //
    // if (!fs.existsSync(path)) {
    //     return null;
    // }
    //
    // return svgToReactComponent(fs.readFileSync(path).toString(), path);
}

function getOcticon(iconName: string): ReactNode {
    const mr = /^octicons\/(.+)$/.exec(iconName);

    if (!mr) {
        return null;
    }

    const name = mr[1];

    if (!octicons[name]) {
        return null;
    }

    return svgToReactComponent(octicons[name].toSVG({'width': 24}), iconName);
}

function getStroykaIcon(iconName: string): ReactNode {
    const mr = /^stroyka\/(.+)$/.exec(iconName);

    if (!mr) {
        return null;
    }

    const icon = mr[1];
    const path = 'src/svg/'+icon+'.svg';

    if (!fs.existsSync(path)) {
        return null;
    }

    for (let iconMode of iconModes) {
        if (iconMode === 'svgSprite') {
            const iconCode = fs.readFileSync(path).toString();
            let attributesString = /<svg([^>]+)>/.exec(iconCode)[1].trim();
            const attributes = {};

            attributesString.match(/((width|height|viewPort)="[^"]*")/g).forEach(x => {
                const mr = x.match(/^([\w-]+)="([^"]*)"$/);

                if (mr) {
                    attributes[mr[1]] = mr[2];
                }
            });

            return <svg {...attributes}><use xlinkHref={`sprite.svg#${mr[1]}`} /></svg>;
        }
        if (iconMode === 'svgInline') {
            return svgToReactComponent(fs.readFileSync(path).toString(), path);
        }
    }

    return 'There is no suitable display mode for the requested icon';
}

const rxjsAutoprefixer = mergeMap<File, Observable<File>>(
    x => from(
        postcss([autoprefixer]).process(x.content.toString(), {from: x.path})
    ).pipe(map(y => ({...x, content: Buffer.from(y.content)})))
);
const rxjsCssNano = mergeMap<File, Observable<File>>(
    x => from(
        postcss([cssnano({preset: 'default'}) as any]).process(x.content.toString(), {from: x.path})
    ).pipe(map(y => ({...x, content: Buffer.from(y.content)})))
);
const appendDirection = (dir: 'ltr'|'rtl') => {
    const append = `@use 'direction' as dir;\n @include dir.configure(${dir}, false);\n`;

    return map<File, File>(file => ({
        ...file,
        content: Buffer.concat([Buffer.from(append), file.content]),
    }));
};

export function buildConfig(mode?: 'demo'|'pack'|'build'): Config {
    return {
        port: 3007,
        distDir: path.join(__dirname, 'dist'),
        pagesDir: 'src/pages',
        pageMiddleware: async html => {
            return !['pack', 'build'].includes(mode) ? html : prettier.format(html, {
                parser: "html",
                tabWidth: 4,
                printWidth: 160,
                htmlWhitespaceSensitivity: 'ignore',
            });
        },
        componentsDir: 'src/components',
        dataDir: 'src/data',
        sass: [
            {
                src: 'src/scss/style.scss',
                dst: 'css',
                middleware: (source$, compile) => source$.pipe(
                    mergeMap(x => merge(
                        of(x),
                        of(x).pipe(renameFileBasename(x => x + '.ltr'), appendDirection('ltr')),
                        of(x).pipe(renameFileBasename(x => x + '.rtl'), appendDirection('rtl')),
                    )),
                    compile,
                    rxjsAutoprefixer,
                    mode === 'demo' ? rxjsCssNano : tap(),
                ),
            },
            {
                src: 'src/scss/bootstrap.scss',
                dst: 'vendor/bootstrap/css',
                middleware: (source$, compile) => source$.pipe(
                    compile,
                    mergeMap(x => merge(
                        of(x).pipe(
                            renameFileBasename(y => y + '.ltr'),
                        ),
                        of(x).pipe(
                            renameFileBasename(y => y + '.rtl'),
                            map(y => ({...y, content: Buffer.from(rtlcss.process(y.content.toString()))})),
                        ),
                    )),
                    rxjsAutoprefixer,
                ),
            },
        ],
        copy: [
            {src: 'src/js/**/*', dst: 'js', watch: true},
            {src: 'src/images/favicon.png', dst: 'images', watch: true},
            {src: 'node_modules/jquery/dist/**/*', dst: 'vendor/jquery'},
            {src: 'node_modules/simplebar/dist/**/*', dst: 'vendor/simplebar'},
            {src: 'node_modules/feather-icons/dist/**/*', dst: 'vendor/feather-icons'},
            {src: 'node_modules/bootstrap/dist/js/**/*', dst: 'vendor/bootstrap/js'},
            {src: 'node_modules/bootstrap-icons/bootstrap-icons.svg', dst: 'vendor/bootstrap-icons'},
            {src: 'node_modules/highlight.js/styles/**/*', dst: 'vendor/highlight.js/styles'},
            {src: 'node_modules/quill/dist/**/*', dst: 'vendor/quill'},
            {src: 'node_modules/air-datepicker/dist/**/*', dst: 'vendor/air-datepicker'},
            {src: 'node_modules/select2/dist/**/*', dst: 'vendor/select2'},
            {src: 'node_modules/@fortawesome/fontawesome-free/{css,js,sprites,svgs,webfonts}/**/*', dst: 'vendor/fontawesome'},
            {src: 'node_modules/@fortawesome/fontawesome-free/LICENSE.txt', dst: 'vendor/fontawesome'},
            {src: 'node_modules/chart.js/dist/**/*', dst: 'vendor/chart.js'},
            {src: 'node_modules/datatables.net/js/**/*', dst: 'vendor/datatables/js'},
            {src: 'node_modules/datatables.net-bs5/{css,js}/**/*', dst: 'vendor/datatables'},
            {src: 'node_modules/flag-icons/flags/flags-iso/flat/24/**/*', dst: 'vendor/flag-icons/24'},
            {src: 'node_modules/nouislider/dist/**/*', dst: 'vendor/nouislider'},
            {src: 'node_modules/fullcalendar/**/*', dst: 'vendor/fullcalendar'},
        ],
        images: {
            src: 'src/images',
            dst: 'images',
            middleware: source$ => mode !== 'pack' ? source$ : source$.pipe(
                mergeMap(x => from(stubImage(x.content, x.path)).pipe(map(y => ({...x, content: y})))),
            ),
        },
        js: [
            {src: 'src/highlight.js', dst: 'vendor/highlight.js/highlight.pack.js'},
        ],
        iconResolver: (iconName) => {
            return getBootstrapIcon(iconName)
                || getFeatherIcon(iconName)
                || getFontAwesomeIcon(iconName)
                || getOcticon(iconName)
                || getStroykaIcon(iconName);
        },
        svg: [
            {src: __dirname + '/src/svg/**/*.svg', dst: 'sprite.svg', watch: true},
        ],
    };
}

const config: Config = buildConfig();

export default config;


