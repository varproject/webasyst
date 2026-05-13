import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import fs from 'fs';
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();
    const bootstrapIcons = [
        'bootstrap/alarm-fill',
        'bootstrap/alarm',
        'bootstrap/basket-fill',
        'bootstrap/archive',
        'bootstrap/battery-full',
        'bootstrap/bicycle',
        'bootstrap/box',
        'bootstrap/bricks',
        'bootstrap/briefcase',
        'bootstrap/brush',
        'bootstrap/basket',
        'bootstrap/bug-fill',
        'bootstrap/calendar',
        'bootstrap/card-image',
        'bootstrap/chat-fill',
        'bootstrap/cpu',
        'bootstrap/egg-fill',
        'bootstrap/folder',
        'bootstrap/hand-thumbs-up',
        'bootstrap/mouse',
    ];

    const featherIcons = [
        'feather/activity',
        'feather/airplay',
        'feather/alert-circle',
        'feather/alert-octagon',
        'feather/alert-triangle',
        'feather/anchor',
        'feather/aperture',
        'feather/archive',
        'feather/at-sign',
        'feather/award',
        'feather/battery',
        'feather/bell',
        'feather/bluetooth',
        'feather/book',
        'feather/box',
        'feather/camera',
        'feather/cast',
        'feather/chrome',
        'feather/clipboard',
        'feather/cloud-drizzle',
        'feather/cloud-lightning',
        'feather/coffee',
        'feather/database',
        'feather/github',
    ];

    const fontAwesomeIcons = [
        'fontawesome/fas fa-archive',
        'fontawesome/fas fa-baby-carriage',
        'fontawesome/fas fa-bacterium',
        'fontawesome/fas fa-ban',
        'fontawesome/fas fa-bed',
        'fontawesome/fas fa-beer',
        'fontawesome/fas fa-blender',
        'fontawesome/fas fa-bolt',
        'fontawesome/fas fa-bong',
        'fontawesome/fas fa-brain',
        'fontawesome/fas fa-desktop',
        'fontawesome/fas fa-bus',
        'fontawesome/fas fa-camera',
        'fontawesome/fas fa-cat',
        'fontawesome/fas fa-chair',
        'fontawesome/fas fa-check',
        'fontawesome/fas fa-cloud',
        'fontawesome/fas fa-columns',
        'fontawesome/fas fa-comments',
        'fontawesome/fas fa-compass',
    ];

    const octicons = [
        'octicons/alert',
        'octicons/archive',
        'octicons/beaker',
        'octicons/bell',
        'octicons/calendar',
        'octicons/checklist',
        'octicons/cpu',
        'octicons/file',
        'octicons/file-directory',
        'octicons/file-media',
        'octicons/flame',
        'octicons/gear',
        'octicons/gift',
        'octicons/heart',
        'octicons/law',
        'octicons/lock',
        'octicons/mail',
        'octicons/people',
        'octicons/person',
        'octicons/rocket',
        'octicons/ruby',
        'octicons/stopwatch',
        'octicons/thumbsup',
        'octicons/tools',
    ];

    const stroykaIcons = fs.readdirSync('src/svg').map((fileName) => (
        'stroyka/' + fileName.substring(0, fileName.length - 4)
    ));

    return (
        <Layout>
            <App>
                <Article
                    title="Icons"
                    subtitle="A list of collections and examples of icons recommended for use in your project along with this templates."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Icons'},
                    ]}
                >
                    <Anchor tag="h2">
                        Bootstrap
                    </Anchor>

                    <p>
                        For the first time ever, Bootstrap has its own icon library, custom designed and built for our
                        components and documentation – and now available for any project.
                    </p>

                    <p>
                        Here is an example of some of these icons. You can find a complete list on the
                        official <a href="https://icons.getbootstrap.com/">website</a>.
                    </p>

                    <Example>
                        <div className="d-flex flex-wrap m-n4 fs-2">
                            {bootstrapIcons.map((iconName) => (
                                <div key={iconName} className="m-4 d-flex">{svg(iconName)}</div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Feather
                    </Anchor>

                    <p>
                        Feather is a collection of simply beautiful open source icons. Each icon is designed on a 24x24
                        grid with an emphasis on simplicity, consistency, and flexibility.
                    </p>

                    <p>
                        Here is an example of some of these icons. You can find a complete list on the
                        official <a href="https://feathericons.com/">website</a>.
                    </p>

                    <Example>
                        <div className="d-flex flex-wrap m-n4 fs-4">
                            {featherIcons.map((iconName) => (
                                <div key={iconName} className="m-4 d-flex">{svg(iconName)}</div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Font Awesome
                    </Anchor>

                    <p>
                        Get vector icons and social logos on your website with Font Awesome, the web's most popular icon
                        set and toolkit.
                    </p>

                    <p>
                        Here is an example of some of these icons. You can find a complete list on the
                        official <a href="https://fontawesome.com/">website</a>.
                    </p>

                    <Example>
                        <div className="d-flex flex-wrap m-n4 fs-2">
                            {fontAwesomeIcons.map((iconName) => (
                                <div key={iconName} className="m-4 d-flex justify-content-center w-2x">
                                    {svg(iconName)}
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Octicons
                    </Anchor>

                    <p>
                        Octicons are a set of SVG icons built by GitHub for GitHub.
                    </p>

                    <p>
                        Here is an example of some of these icons. You can find a complete list on the
                        official <a href="https://primer.style/octicons/" target="_blank">website</a>.
                    </p>

                    <Example>
                        <div className="d-flex flex-wrap m-n4">
                            {octicons.map((iconName) => (
                                <div key={iconName} className="m-4 d-flex">{svg(iconName)}</div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Stroyka
                    </Anchor>

                    <p>
                        The template also uses several self-made icons. Here is a list of these icons:
                    </p>

                    <Example>
                        <div className="d-flex flex-wrap m-n4">
                            {stroykaIcons.map((iconName) => (
                                <div key={iconName} className="m-4 d-flex align-items-center">{svg(iconName)}</div>
                            ))}
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
