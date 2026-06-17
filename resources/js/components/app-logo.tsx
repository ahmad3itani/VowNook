export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md border border-[#e7ddcb] bg-[#faf6ef]">
                <img src="/images/brand/logo-mark.webp" alt="VowNook" className="size-full object-contain" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="mb-0.5 truncate font-['Fraunces'] text-base leading-tight font-semibold tracking-tight">
                    VowNook
                </span>
            </div>
        </>
    );
}
