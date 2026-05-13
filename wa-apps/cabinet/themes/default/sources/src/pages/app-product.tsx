import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classnames from 'classnames';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import Image from "../components/Image";
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

type CardProps = {
    title?: string;
    help?: string;
    className?: string;
    body?: React.ReactNode;
    children?: React.ReactNode;
};

const Card = ({title, help, children, body, className}: CardProps) => (
    <div className={classnames('card', className)}>
        <div className="card-body p-5">
            {title && (
                <div className="mb-5">
                    <h2 className="mb-0 fs-exact-18">{title}</h2>
                    {help && <div className="mt-3 text-muted">{help}</div>}
                </div>
            )}
            {children}
        </div>
        {body}
    </div>
);

export default function() {
    const svg = useSvg();
    const imageSize = 16 * 2.5;

    const images = [
        'images/products/product-16-1.jpg',
        'images/products/product-16-2.jpg',
        'images/products/product-16-3.jpg',
        'images/products/product-16-4.jpg',
    ];

    const product = {
        sku: 'SCREW150',
        name: 'Brandix Screwdriver SCREW150',
        slug: 'brandix-screwdriver-screw150',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur ornare, mi in ornare elementum, libero nibh lacinia urna, quis convallis lorem erat at purus. Maecenas eu varius nisi.',
        price: 1499,
        quantity: 18,
    };

    const main = (
        <>
            <Card title="Basic information">
                <div className="mb-4">
                    <label htmlFor="form-product/name" className="form-label">
                        Name
                    </label>
                    <input type="text" className="form-control" id="form-product/name" defaultValue={product.name} />
                </div>
                <div className="mb-4">
                    <label htmlFor="form-product/slug" className="form-label">
                        Slug
                    </label>
                    <div className="input-group input-group--sa-slug">
                        <span className="input-group-text" id="form-product/slug-addon">
                            https://example.com/products/
                        </span>
                        <input
                            type="text"
                            className="form-control"
                            id="form-product/slug"
                            aria-describedby="form-product/slug-addon form-product/slug-help"
                            defaultValue={product.slug}
                        />
                    </div>
                    <div id="form-product/slug-help" className="form-text">
                        Unique human-readable product identifier. No longer than 255 characters.
                    </div>
                </div>
                <div className="mb-4">
                    <label htmlFor="form-product/description" className="form-label">
                        Description
                    </label>
                    <textarea
                        id="form-product/description"
                        className="sa-quill-control form-control"
                        rows={8}
                        defaultValue={product.description}
                    />
                </div>
                <div>
                    <label htmlFor="form-product/short-description" className="form-label">
                        Short description
                    </label>
                    <textarea
                        id="form-product/short-description"
                        className="form-control"
                        rows={2}
                    />
                </div>
            </Card>

            <Card title="Pricing" className="mt-5">
                <div className="row g-4">
                    <div className="col">
                        <label htmlFor="form-product/price" className="form-label">
                            Price
                        </label>
                        <input type="number" className="form-control" id="form-product/price" defaultValue={product.price} />
                    </div>
                    <div className="col">
                        <label htmlFor="form-product/old-price" className="form-label">
                            Old price
                        </label>
                        <input type="number" className="form-control" id="form-product/old-price" />
                    </div>
                </div>
                <div className="mt-4 mb-n2">
                    <a href="#">Schedule discount</a>
                </div>
            </Card>

            <Card title="Inventory" className="mt-5">
                <div className="mb-4">
                    <label htmlFor="form-product/sku" className="form-label">
                        SKU
                    </label>
                    <input type="text" className="form-control" id="form-product/sku" defaultValue={product.sku} />
                </div>
                <div className="mb-4 pt-2">
                    <label className="form-check">
                        <input type="checkbox" className="form-check-input"/>
                        <span className="form-check-label">
                            Enable stock management
                        </span>
                    </label>
                </div>
                <div>
                    <label htmlFor="form-product/quantity" className="form-label">
                        Stock quantity
                    </label>
                    <input type="number" className="form-control" id="form-product/quantity" defaultValue={product.quantity} />
                </div>
            </Card>

            <Card
                title="Images"
                className="mt-5"
                body={
                    <div className="mt-n5">
                        <div className="sa-divider" />
                        <div className="table-responsive">
                            <table className="sa-table">
                                <thead>
                                    <tr>
                                        <th className="w-min">Image</th>
                                        <th className="min-w-10x">Alt text</th>
                                        <th className="w-min">Order</th>
                                        <th className="w-min" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {images.map((image, imageIdx) => (
                                        <tr key={imageIdx}>
                                            <td>
                                                <div className="sa-symbol sa-symbol--shape--rounded sa-symbol--size--lg">
                                                    <Image src={image} size={imageSize} />
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" className="form-control form-control-sm" />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    className="form-control form-control-sm w-4x"
                                                    defaultValue={imageIdx}
                                                />
                                            </td>
                                            <td>
                                                <button
                                                    className="btn btn-sa-muted btn-sm mx-n3"
                                                    type="button"
                                                    aria-label="Delete image"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="right"
                                                    title="Delete image"
                                                >
                                                    {svg('stroyka/cross-12')}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="sa-divider" />
                        <div className="px-5 py-4 my-2">
                            <a href="#">Upload new image</a>
                        </div>
                    </div>
                }
            />

            <Card
                title="Search engine optimization"
                help="Provide information that will help improve the snippet and bring your product to the top of search engines."
                className="mt-5"
            >
                <div className="mb-4">
                    <label htmlFor="form-product/seo-title" className="form-label">
                        Page title
                    </label>
                    <input type="text" className="form-control" id="form-product/seo-title" />
                </div>
                <div>
                    <label htmlFor="form-product/seo-description" className="form-label">
                        Meta description
                    </label>
                    <textarea
                        id="form-product/seo-description"
                        className="form-control"
                        rows={2}
                    />
                </div>
            </Card>
        </>
    );

    const sidebar = (
        <>
            <Card title="Visibility" className="w-100">
                <div className="mb-4">
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="status" />
                        <span className="form-check-label">Published</span>
                    </label>
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="status" defaultChecked />
                        <span className="form-check-label">Scheduled</span>
                    </label>
                    <label className="form-check mb-0">
                        <input type="radio" className="form-check-input" name="status" />
                        <span className="form-check-label">Hidden</span>
                    </label>
                </div>
                <div>
                    <label htmlFor="form-product/seo-title" className="form-label">
                        Publish date
                    </label>
                    <input
                        type="text"
                        className="form-control datepicker-here"
                        id="form-product/publish-date"
                        data-auto-close="true"
                        data-language="en"
                    />
                </div>
            </Card>
            <Card title="Categories" className="w-100 mt-5">
                <select className="sa-select2 form-select" multiple>
                    <option selected>Power tools</option>
                    <option>Screwdrivers</option>
                    <option selected>Chainsaws</option>
                    <option>Hand tools</option>
                    <option>Machine tools</option>
                    <option>Power machinery</option>
                    <option>Measurements</option>
                </select>

                <div className="mt-4 mb-n2">
                    <a href="#">Add new category</a>
                </div>
            </Card>
            <Card title="Tags" className="w-100 mt-5">
                <select className="sa-select2 form-select" data-tags="true" multiple>
                    <option selected>Universe</option>
                    <option selected>Sputnik</option>
                    <option selected>Steel</option>
                    <option selected>Rocket</option>
                </select>
            </Card>
        </>
    );

    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container">
                        <PageHeader
                            title="Edit Product"
                            actions={[
                                <a key="duplicate" href="#" className="btn btn-secondary me-3">
                                    Duplicate
                                </a>,
                                <a key="save" href="#" className="btn btn-primary">
                                    Save
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Products', url: url('products-list')},
                                {title: 'Edit Product', url: url('product')},
                            ]}
                        />
                        <div
                            className="sa-entity-layout"
                            data-sa-container-query={JSON.stringify({920: 'sa-entity-layout--size--md', 1100: 'sa-entity-layout--size--lg'})}
                        >
                            <div className="sa-entity-layout__body">
                                <div className="sa-entity-layout__main">
                                    {main}
                                </div>
                                <div className="sa-entity-layout__sidebar">
                                    {sidebar}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
