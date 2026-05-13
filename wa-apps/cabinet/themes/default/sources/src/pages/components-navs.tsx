import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <App>
                <Article
                    title="Navs"
                    subtitle="Documentation and examples for how to use Bootstrap's included navigation components."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Navs'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Navigation available in Bootstrap share general markup and styles, from the
                        base <code>.nav</code> class to the active and disabled states. Swap modifier classes to switch
                        between each style.
                    </p>

                    <Example>
                        <ul className="nav">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Horizontal Alignment
                    </Anchor>

                    <p>
                        Change the horizontal alignment of your nav with flexbox utilities. By default, navs are
                        left-aligned, but you can easily change them to center or right aligned.
                    </p>

                    <p>
                        Centered with <code>.justify-content-center</code>:
                    </p>

                    <Example>
                        <ul className="nav justify-content-center">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <p>
                        Right-aligned with <code>.justify-content-end</code>:
                    </p>

                    <Example>
                        <ul className="nav justify-content-end">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Vertical
                    </Anchor>

                    <p>
                        Stack your navigation by changing the flex item direction with
                        the <code>.flex-column</code> utility. Need to stack them on some viewports but not others? Use
                        the responsive versions (e.g., <code>.flex-sm-column</code>).
                    </p>

                    <Example>
                        <ul className="nav flex-column">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Pills
                    </Anchor>

                    <p>
                        Take that same HTML, but use <code>.nav-pills</code> instead:
                    </p>

                    <Example>
                        <ul className="nav nav-pills">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Fill And Justify
                    </Anchor>

                    <p>
                        Force your <code>.nav</code>'s contents to extend the full available width one of two modifier
                        classes. To proportionately fill all available space with your <code>.nav-item</code>'s,
                        use <code>.nav-fill</code>. Notice that all horizontal space is occupied, but not every nav item
                        has the same width.
                    </p>

                    <Example>
                        <ul className="nav nav-pills nav-fill">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Much longer nav link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <p>
                        For equal-width elements, use <code>.nav-justified</code>. All horizontal space will be occupied
                        by nav links, but unlike the <code>.nav-fill</code> above, every nav item will be the same
                        width.
                    </p>

                    <Example>
                        <ul className="nav nav-pills nav-justified align-items-stretch">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Much longer nav link</a>
                            </li>
                            <li className="nav-item">
                                <a className="nav-link" href="#">Link</a>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Working With Flex Utilities
                    </Anchor>

                    <p>
                        If you need responsive nav variations, consider using a series of <a
                        href="https://getbootstrap.com/docs/5.0/utilities/flex/">flexbox utilities</a>. While more
                        verbose, these utilities offer greater customization across responsive breakpoints. In the
                        example below, our nav will be stacked on the lowest breakpoint, then adapt to a horizontal
                        layout that fills the available width starting from the small breakpoint.
                    </p>

                    <Example>
                        <nav className="nav nav-pills flex-column flex-sm-row">
                            <a
                                className="flex-sm-fill text-sm-center nav-link active"
                                aria-current="page"
                                href="#"
                            >
                                Active
                            </a>
                            <a className="flex-sm-fill text-sm-center nav-link" href="#">Longer nav link</a>
                            <a className="flex-sm-fill text-sm-center nav-link" href="#">Link</a>
                            <a
                                className="flex-sm-fill text-sm-center nav-link disabled"
                                tabIndex={-1}
                                aria-disabled="true"
                            >
                                Disabled
                            </a>
                        </nav>
                    </Example>

                    <Anchor tag="h2">
                        Using Dropdowns
                    </Anchor>

                    <p>
                        Add dropdown menus with a little extra HTML and the dropdowns JavaScript plugin.
                    </p>

                    <Example>
                        <ul className="nav nav-pills">
                            <li className="nav-item">
                                <a className="nav-link active" aria-current="page" href="#">Active</a>
                            </li>
                            <li className="nav-item dropstart">
                                <a
                                    className="nav-link dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    href="#"
                                    role="button"
                                    aria-expanded="false"
                                >
                                    Start
                                </a>
                                <ul className="dropdown-menu">
                                    <li><a className="dropdown-item" href="#">Action</a></li>
                                    <li><a className="dropdown-item" href="#">Another action</a></li>
                                    <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    <li>
                                        <hr className="dropdown-divider" />
                                    </li>
                                    <li><a className="dropdown-item" href="#">Separated link</a></li>
                                </ul>
                            </li>
                            <li className="nav-item dropdown">
                                <a
                                    className="nav-link dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    href="#"
                                    role="button"
                                    aria-expanded="false"
                                >
                                    Down
                                </a>
                                <ul className="dropdown-menu">
                                    <li><a className="dropdown-item" href="#">Action</a></li>
                                    <li><a className="dropdown-item" href="#">Another action</a></li>
                                    <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    <li>
                                        <hr className="dropdown-divider" />
                                    </li>
                                    <li><a className="dropdown-item" href="#">Separated link</a></li>
                                </ul>
                            </li>
                            <li className="nav-item dropup">
                                <a
                                    className="nav-link dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    href="#"
                                    role="button"
                                    aria-expanded="false"
                                >
                                    Up
                                </a>
                                <ul className="dropdown-menu">
                                    <li><a className="dropdown-item" href="#">Action</a></li>
                                    <li><a className="dropdown-item" href="#">Another action</a></li>
                                    <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    <li>
                                        <hr className="dropdown-divider" />
                                    </li>
                                    <li><a className="dropdown-item" href="#">Separated link</a></li>
                                </ul>
                            </li>
                            <li className="nav-item dropend">
                                <a
                                    className="nav-link dropdown-toggle"
                                    data-bs-toggle="dropdown"
                                    href="#"
                                    role="button"
                                    aria-expanded="false"
                                >
                                    End
                                </a>
                                <ul className="dropdown-menu">
                                    <li><a className="dropdown-item" href="#">Action</a></li>
                                    <li><a className="dropdown-item" href="#">Another action</a></li>
                                    <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    <li>
                                        <hr className="dropdown-divider" />
                                    </li>
                                    <li><a className="dropdown-item" href="#">Separated link</a></li>
                                </ul>
                            </li>
                            <li className="nav-item">
                                <a
                                    className="nav-link disabled"
                                    tabIndex={-1}
                                    aria-disabled="true"
                                >
                                    Disabled
                                </a>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Live Example
                    </Anchor>

                    <p>
                        Use the tab JavaScript plugin to extend our navigational pills to create tabbable panes of local
                        content.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-header">
                                <ul className="nav nav-pills" role="tablist">
                                    <li className="nav-item" role="presentation">
                                        <button className="nav-link active" id="pills-home-tab" data-bs-toggle="pill"
                                                data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home"
                                                aria-selected="true">Home
                                        </button>
                                    </li>
                                    <li className="nav-item" role="presentation">
                                        <button className="nav-link" id="pills-profile-tab" data-bs-toggle="pill"
                                                data-bs-target="#pills-profile" type="button" role="tab"
                                                aria-controls="pills-profile" aria-selected="false">Profile
                                        </button>
                                    </li>
                                    <li className="nav-item" role="presentation">
                                        <button className="nav-link" id="pills-contact-tab" data-bs-toggle="pill"
                                                data-bs-target="#pills-contact" type="button" role="tab"
                                                aria-controls="pills-contact" aria-selected="false">Contact
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div className="card-body">
                                <div className="tab-content">
                                    <div
                                        className="tab-pane fade show active"
                                        id="pills-home"
                                        role="tabpanel"
                                        aria-labelledby="pills-home-tab"
                                    >
                                        Placeholder content for the tab panel. This one relates to the home tab. Takes you miles
                                        high, so high, 'cause she's got that one international smile. There's a stranger in my
                                        bed, there's a pounding in my head. Oh, no. In another life I would make you stay.
                                        'Cause I, I'm capable of anything. Suiting up for my crowning battle. Used to steal your
                                        parents' liquor and climb to the roof. Tone, tan fit and ready, turn it up cause its
                                        gettin' heavy. Her love is like a drug. I guess that I forgot I had a choice.
                                    </div>
                                    <div
                                        className="tab-pane fade"
                                        id="pills-profile"
                                        role="tabpanel"
                                        aria-labelledby="pills-profile-tab"
                                    >
                                        Placeholder content for the tab panel. This one relates to the profile tab. You got the
                                        finest architecture. Passport stamps, she's cosmopolitan. Fine, fresh, fierce, we got it
                                        on lock. Never planned that one day I'd be losing you. She eats your heart out. Your
                                        kiss is cosmic, every move is magic. I mean the ones, I mean like she's the one.
                                        Greetings loved ones let's take a journey. Just own the night like the 4th of July! But
                                        you'd rather get wasted.
                                    </div>
                                    <div
                                        className="tab-pane fade"
                                        id="pills-contact"
                                        role="tabpanel"
                                        aria-labelledby="pills-contact-tab"
                                    >
                                        Placeholder content for the tab panel. This one relates to the contact tab. Her love is
                                        like a drug. All my girls vintage Chanel baby. Got a motel and built a fort out of
                                        sheets. 'Cause she's the muse and the artist. (This is how we do) So you wanna play with
                                        magic. So just be sure before you give it all to me. I'm walking, I'm walking on air
                                        (tonight). Skip the talk, heard it all, time to walk the walk. Catch her if you can.
                                        Stinging like a bee I earned my stripes.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
