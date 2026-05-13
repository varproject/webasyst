import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    let exampleId = 0;

    return (
        <Layout>
            <App>
                <Article
                    title="NoUiSlider"
                    subtitle="Lightweight JavaScript range slider library with full multi-touch support."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'NoUiSlider'},
                    ]}
                >
                    {++exampleId && false}

                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        noUiSlider requires manual initialization via javascript.
                        Use <code>stroyka.nouislider.create</code> instead of <code>noUiSlider.create</code>.
                    </p>

                    <Example>
                        <div id={`example/nouislider-${exampleId}`} />

                        <script dangerouslySetInnerHTML={{__html: `
                            window.addEventListener('load', function() {
                                var slider = document.getElementById('example/nouislider-${exampleId}');

                                stroyka.nouislider.create(slider, {
                                    start: [25, 75],
                                    connect: true,
                                    range: {
                                        'min': 0,
                                        'max': 100
                                    }
                                });
                            });
                        `}} />
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    {++exampleId && false}

                    <Anchor tag="h2">
                        With inputs
                    </Anchor>

                    <p>
                        Connect the slider to the inputs.
                    </p>

                    <Example>
                        <div id={`example/nouislider-${exampleId}`} />

                        <div className="d-flex align-items-center mt-4">
                            <input type="number" className="form-control form-control-sm" id={`example/nouislider-${exampleId}/input-from`} />
                            <div className="sa-dash flex-shrink-0 mx-3" />
                            <input type="number" className="form-control form-control-sm" id={`example/nouislider-${exampleId}/input-to`} />
                        </div>

                        <script dangerouslySetInnerHTML={{__html: `
                            window.addEventListener('load', function() {
                                var slider = document.getElementById('example/nouislider-${exampleId}');

                                stroyka.nouislider.create(slider, {
                                    start: [25, 75],
                                    connect: true,
                                    range: {
                                        'min': 0,
                                        'max': 100
                                    }
                                });

                                const inputs = [
                                    document.getElementById('example/nouislider-${exampleId}/input-from'),
                                    document.getElementById('example/nouislider-${exampleId}/input-to'),
                                ];

                                slider.noUiSlider.on('update', function (values, handle) {
                                    inputs[handle].value = values[handle];
                                });

                                const readValue = function() {
                                    const value = inputs.map(function(input) {
                                        return parseFloat(input.value);
                                    });

                                    slider.noUiSlider.set(value);
                                };

                                inputs.forEach(function(input) {
                                    input.addEventListener('change', function() {
                                        readValue();
                                    });
                                });
                            });
                        `}} />
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    {++exampleId && false}

                    <Anchor tag="h2">
                        Tooltips
                    </Anchor>

                    <p>
                        To display tooltips, pass the <code>tooltips</code> option with a <code>true</code> value.
                    </p>

                    <Example>
                        <div id={`example/nouislider-${exampleId}`} />

                        <script dangerouslySetInnerHTML={{__html: `
                            window.addEventListener('load', function() {
                                var slider = document.getElementById('example/nouislider-${exampleId}');

                                stroyka.nouislider.create(slider, {
                                    start: [25, 75],
                                    connect: true,
                                    tooltips: true,
                                    range: {
                                        'min': 0,
                                        'max': 100
                                    }
                                });
                            });
                        `}} />
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    {++exampleId && false}

                    <Anchor tag="h2">
                        Ruler
                    </Anchor>

                    <p>
                        Use <code>pips</code> option to show ruler.
                    </p>

                    <Example>
                        <div id={`example/nouislider-${exampleId}`} />

                        <script dangerouslySetInnerHTML={{__html: `
                            window.addEventListener('load', function() {
                                var slider = document.getElementById('example/nouislider-${exampleId}');

                                stroyka.nouislider.create(slider, {
                                    start: [25, 75],
                                    connect: true,
                                    tooltips: true,
                                    range: {
                                        'min': 0,
                                        'max': 100
                                    },
                                    pips: {
                                        mode: 'count',
                                        values: 5,
                                        density: 2,
                                    }
                                });
                            });
                        `}} />
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    {++exampleId && false}

                    <Anchor tag="h2">
                        Stepping
                    </Anchor>

                    <p>
                        The amount the slider changes on movement can be set using the <code>step</code> option.
                    </p>

                    <Example>
                        <div id={`example/nouislider-${exampleId}`} />

                        <script dangerouslySetInnerHTML={{__html: `
                            window.addEventListener('load', function() {
                                var slider = document.getElementById('example/nouislider-${exampleId}');

                                stroyka.nouislider.create(slider, {
                                    start: [25, 75],
                                    step: 25,
                                    connect: true,
                                    tooltips: true,
                                    range: {
                                        'min': 0,
                                        'max': 100
                                    },
                                    pips: {
                                        mode: 'steps',
                                        density: 2,
                                        stepped: true,
                                    }
                                });
                            });
                        `}} />
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    {++exampleId && false}

                    <Anchor tag="h2">
                        Vertical
                    </Anchor>

                    <p>
                        Set the <code>orientation</code> option to <code>vertical</code> value.
                    </p>

                    <Example>
                        <div id={`example/nouislider-${exampleId}`} />

                        <script dangerouslySetInnerHTML={{__html: `
                            window.addEventListener('load', function() {
                                var slider = document.getElementById('example/nouislider-${exampleId}');

                                slider.style.height = '318px';

                                stroyka.nouislider.create(slider, {
                                    start: [25, 75],
                                    connect: true,
                                    tooltips: true,
                                    orientation: 'vertical',
                                    range: {
                                        'min': 0,
                                        'max': 100
                                    },
                                    pips: {
                                        mode: 'count',
                                        values: 5,
                                        density: 2,
                                    }
                                });
                            });
                        `}} />
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
