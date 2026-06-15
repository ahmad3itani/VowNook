import type { SVGAttributes } from 'react';

// Two interlocking rings — the VowNook mark.
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 42 42" xmlns="http://www.w3.org/2000/svg">
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M15 8C8.925 8 4 12.925 4 19s4.925 11 11 11c2.05 0 3.97-.561 5.614-1.538a14.04 14.04 0 0 1-1.708-2.42A7.96 7.96 0 0 1 15 27c-4.418 0-8-3.582-8-8s3.582-8 8-8a7.96 7.96 0 0 1 3.906.958 14.04 14.04 0 0 1 1.708-2.42A10.95 10.95 0 0 0 15 8Z"
            />
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M27 12c-6.075 0-11 4.925-11 11s4.925 11 11 11 11-4.925 11-11-4.925-11-11-11Zm0 3c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8Z"
            />
        </svg>
    );
}
