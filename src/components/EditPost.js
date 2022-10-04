import React, { useEffect, useState } from "react";
import ReactJson from "react-json-view";
import Header from "./header";
import { Link } from "react-router-dom";
import { Oval } from "react-loader-spinner";
import axios from "axios";

export const tickIcon = (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="14"
    height="14"
    viewBox="0 0 24 24"
    fill="none"
    stroke="#22BB33"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="feather feather-check"
  >
    <polyline points="20 6 9 17 4 12"></polyline>
  </svg>
);

export const aeroSvg = (
  <svg
    width="140"
    height="29.75"
    viewBox="0 0 800 170"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <rect width="169.977" height="169.977" rx="33.8254" fill="#252525" />
    <path
      d="M89.2477 98.1623H69.3319C74.9853 98.3026 80.473 100.044 85.1195 103.172C89.766 106.301 93.3686 110.679 95.4835 115.769L89.2477 98.1623Z"
      stroke="#FAFAFA"
      stroke-width="7.44159"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <path
      d="M84.6257 102.847C80.9259 100.464 76.7114 98.9378 72.3074 98.3853C67.9034 97.8329 63.4277 98.2694 59.225 99.6612C55.0224 101.053 51.2053 103.363 48.0682 106.413C44.931 109.463 42.5574 113.171 41.1308 117.252L38.4376 124.955C38.3844 125.108 38.3688 125.27 38.3931 125.429C38.4175 125.588 38.4806 125.739 38.5771 125.87C38.6736 126 38.8007 126.107 38.948 126.18C39.0953 126.253 39.2585 126.292 39.4241 126.292H67.5469C67.9824 126.292 68.4071 126.159 68.7612 125.913C69.1152 125.667 69.3805 125.319 69.5205 124.919L71.9311 118.025C74.1576 111.656 78.6611 106.272 84.6257 102.847V102.847Z"
      stroke="#FAFAFA"
      stroke-width="7.44159"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <path
      d="M84.0274 83.4252L70.3745 44.8795C70.2331 44.4804 69.9665 44.1342 69.6123 43.8894C69.258 43.6447 68.834 43.5136 68.3991 43.5145C67.9642 43.5155 67.5404 43.6483 67.1872 43.8946C66.834 44.1409 66.5688 44.4882 66.4291 44.8879L41.1312 117.252C43.1186 111.568 46.9276 106.648 52.0014 103.214C57.0751 99.7794 63.1474 98.0096 69.3315 98.163H78.8755L84.0274 83.4252Z"
      stroke="#FAFAFA"
      stroke-width="7.44159"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <path
      d="M101.445 44.8794C101.304 44.4809 101.038 44.1351 100.685 43.8904C100.331 43.6456 99.9078 43.5141 99.4736 43.5141H68.3992C68.834 43.5133 69.2587 43.6445 69.6129 43.8893C69.9671 44.1341 70.2332 44.4803 70.3746 44.8794L95.4831 115.769C95.8607 116.678 96.1893 117.605 96.4672 118.547L98.7263 124.926C98.8675 125.325 99.1334 125.671 99.4868 125.915C99.8402 126.16 100.264 126.292 100.698 126.292H128.82C128.986 126.292 129.15 126.253 129.298 126.179C129.445 126.106 129.572 125.999 129.668 125.868C129.765 125.737 129.828 125.585 129.852 125.426C129.876 125.266 129.86 125.104 129.806 124.952L101.445 44.8794Z"
      stroke="#FAFAFA"
      stroke-width="7.44159"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <path
      d="M267.493 114.355H233.753L228.345 130.323H205.294L238.003 39.922H263.501L296.21 130.323H272.901L267.493 114.355ZM261.827 97.3565L250.623 64.2608L239.548 97.3565H261.827ZM366.175 93.2356C366.175 95.296 366.046 97.4423 365.789 99.6745H315.952C316.296 104.139 317.712 107.573 320.202 109.977C322.777 112.295 325.911 113.454 329.603 113.454C335.097 113.454 338.917 111.136 341.064 106.5H364.501C363.299 111.221 361.11 115.471 357.933 119.249C354.843 123.026 350.937 125.988 346.215 128.134C341.493 130.28 336.213 131.354 330.375 131.354C323.335 131.354 317.068 129.851 311.574 126.846C306.079 123.842 301.787 119.549 298.696 113.969C295.605 108.388 294.06 101.864 294.06 94.3946C294.06 86.9256 295.563 80.4009 298.567 74.8205C301.658 69.2402 305.951 64.9477 311.445 61.9429C316.939 58.9381 323.25 57.4357 330.375 57.4357C337.329 57.4357 343.51 58.8951 348.919 61.8141C354.328 64.733 358.534 68.8968 361.539 74.3054C364.63 79.7141 366.175 86.0241 366.175 93.2356ZM343.639 87.4407C343.639 83.6632 342.351 80.6584 339.776 78.4263C337.2 76.1942 333.981 75.0781 330.118 75.0781C326.426 75.0781 323.292 76.1512 320.717 78.2975C318.227 80.4438 316.682 83.4915 316.081 87.4407H343.639ZM392.086 70.4421C394.662 66.493 397.881 63.4023 401.745 61.1702C405.608 58.8522 409.901 57.6932 414.622 57.6932V81.0018H408.57C403.075 81.0018 398.954 82.2037 396.207 84.6076C393.46 86.9256 392.086 91.0464 392.086 96.9701V130.323H370.066V58.4659H392.086V70.4421ZM450.13 131.354C443.091 131.354 436.738 129.851 431.072 126.846C425.491 123.842 421.07 119.549 417.808 113.969C414.631 108.388 413.043 101.864 413.043 94.3946C413.043 87.0114 414.674 80.5296 417.936 74.9493C421.199 69.2831 425.663 64.9477 431.329 61.9429C436.995 58.9381 443.348 57.4357 450.388 57.4357C457.428 57.4357 463.781 58.9381 469.447 61.9429C475.113 64.9477 479.577 69.2831 482.84 74.9493C486.102 80.5296 487.733 87.0114 487.733 94.3946C487.733 101.778 486.059 108.302 482.711 113.969C479.449 119.549 474.941 123.842 469.189 126.846C463.523 129.851 457.17 131.354 450.13 131.354ZM450.13 112.295C454.337 112.295 457.9 110.749 460.819 107.659C463.824 104.568 465.326 100.147 465.326 94.3946C465.326 88.6426 463.867 84.2212 460.948 81.1306C458.115 78.04 454.595 76.4946 450.388 76.4946C446.095 76.4946 442.533 78.04 439.7 81.1306C436.866 84.1354 435.45 88.5567 435.45 94.3946C435.45 100.147 436.824 104.568 439.571 107.659C442.404 110.749 445.924 112.295 450.13 112.295ZM513.71 68.6393C515.856 65.2911 518.818 62.5867 522.596 60.5263C526.373 58.4659 530.794 57.4357 535.86 57.4357C541.783 57.4357 547.149 58.9381 551.957 61.9429C556.764 64.9477 560.542 69.2402 563.289 74.8205C566.122 80.4009 567.539 86.8826 567.539 94.2658C567.539 101.649 566.122 108.174 563.289 113.84C560.542 119.42 556.764 123.756 551.957 126.846C547.149 129.851 541.783 131.354 535.86 131.354C530.88 131.354 526.459 130.323 522.596 128.263C518.818 126.202 515.856 123.541 513.71 120.279V164.578H491.689V58.4659H513.71V68.6393ZM545.132 94.2658C545.132 88.7714 543.586 84.4788 540.496 81.3882C537.491 78.2117 533.756 76.6234 529.292 76.6234C524.914 76.6234 521.179 78.2117 518.088 81.3882C515.084 84.5646 513.581 88.9001 513.581 94.3946C513.581 99.8891 515.084 104.225 518.088 107.401C521.179 110.578 524.914 112.166 529.292 112.166C533.67 112.166 537.405 110.578 540.496 107.401C543.586 104.139 545.132 99.7603 545.132 94.2658ZM566.986 94.2658C566.986 86.8826 568.36 80.4009 571.107 74.8205C573.94 69.2402 577.761 64.9477 582.568 61.9429C587.376 58.9381 592.742 57.4357 598.665 57.4357C603.731 57.4357 608.152 58.4659 611.929 60.5263C615.793 62.5867 618.755 65.2911 620.815 68.6393V58.4659H642.836V130.323H620.815V120.15C618.669 123.498 615.664 126.202 611.801 128.263C608.023 130.323 603.602 131.354 598.537 131.354C592.699 131.354 587.376 129.851 582.568 126.846C577.761 123.756 573.94 119.42 571.107 113.84C568.36 108.174 566.986 101.649 566.986 94.2658ZM620.815 94.3946C620.815 88.9001 619.27 84.5646 616.179 81.3882C613.174 78.2117 609.483 76.6234 605.104 76.6234C600.726 76.6234 596.991 78.2117 593.901 81.3882C590.896 84.4788 589.393 88.7714 589.393 94.2658C589.393 99.7603 590.896 104.139 593.901 107.401C596.991 110.578 600.726 112.166 605.104 112.166C609.483 112.166 613.174 110.578 616.179 107.401C619.27 104.225 620.815 99.8891 620.815 94.3946ZM678.341 57.4357C683.406 57.4357 687.828 58.4659 691.605 60.5263C695.468 62.5867 698.43 65.2911 700.491 68.6393V58.4659H722.511V130.195C722.511 136.805 721.181 142.772 718.519 148.095C715.944 153.503 711.952 157.796 706.543 160.972C701.22 164.149 694.567 165.737 686.583 165.737C675.937 165.737 667.309 163.204 660.699 158.139C654.088 153.16 650.311 146.377 649.366 137.792H671.13C671.816 140.54 673.447 142.686 676.023 144.231C678.599 145.862 681.775 146.678 685.553 146.678C690.103 146.678 693.708 145.347 696.37 142.686C699.117 140.11 700.491 135.947 700.491 130.195V120.021C698.344 123.369 695.382 126.117 691.605 128.263C687.828 130.323 683.406 131.354 678.341 131.354C672.417 131.354 667.052 129.851 662.244 126.846C657.436 123.756 653.616 119.42 650.783 113.84C648.036 108.174 646.662 101.649 646.662 94.2658C646.662 86.8826 648.036 80.4009 650.783 74.8205C653.616 69.2402 657.436 64.9477 662.244 61.9429C667.052 58.9381 672.417 57.4357 678.341 57.4357ZM700.491 94.3946C700.491 88.9001 698.945 84.5646 695.855 81.3882C692.85 78.2117 689.158 76.6234 684.78 76.6234C680.401 76.6234 676.667 78.2117 673.576 81.3882C670.571 84.4788 669.069 88.7714 669.069 94.2658C669.069 99.7603 670.571 104.139 673.576 107.401C676.667 110.578 680.401 112.166 684.78 112.166C689.158 112.166 692.85 110.578 695.855 107.401C698.945 104.225 700.491 99.8891 700.491 94.3946ZM798.452 93.2356C798.452 95.296 798.324 97.4423 798.066 99.6745H748.23C748.573 104.139 749.989 107.573 752.479 109.977C755.055 112.295 758.188 113.454 761.88 113.454C767.374 113.454 771.195 111.136 773.341 106.5H796.778C795.576 111.221 793.387 115.471 790.211 119.249C787.12 123.026 783.214 125.988 778.492 128.134C773.77 130.28 768.49 131.354 762.653 131.354C755.613 131.354 749.346 129.851 743.851 126.846C738.357 123.842 734.064 119.549 730.973 113.969C727.883 108.388 726.337 101.864 726.337 94.3946C726.337 86.9256 727.84 80.4009 730.845 74.8205C733.935 69.2402 738.228 64.9477 743.722 61.9429C749.217 58.9381 755.527 57.4357 762.653 57.4357C769.606 57.4357 775.788 58.8951 781.196 61.8141C786.605 64.733 790.812 68.8968 793.817 74.3054C796.907 79.7141 798.452 86.0241 798.452 93.2356ZM775.917 87.4407C775.917 83.6632 774.629 80.6584 772.053 78.4263C769.478 76.1942 766.258 75.0781 762.395 75.0781C758.703 75.0781 755.57 76.1512 752.994 78.2975C750.505 80.4438 748.959 83.4915 748.358 87.4407H775.917Z"
      fill="black"
    />
  </svg>
);

const EditPost = ({ resetView, id, editTitle, url, editDynamic }) => {
  const JSON = {};

  const [btnState, setBtnState] = useState(true);
  const [inputValue, setInputValue] = useState("");
  const [status, setStatus] = useState(true);
  const [title, setTitle] = useState(editTitle);
  const [slug, setSlug] = useState(url);
  const [dynamic, setDynamic] = useState(editDynamic);
  const [responseAP, setResponseAP] = useState(null);

  const handleSubmit = (e) => {
    e.preventDefault();
    console.log(MYSCRIPT.ajaxUrl);

    // const reactAppData = window.wpRoomDesigner || {};
    // const { ajax_url } = reactAppData;
    var params = new URLSearchParams();
    params.append("action", "myAction");
    params.append("title", title);
    params.append("id", id);
    params.append("dynamic", dynamic);
    params.append("slug", slug);
    params.append("token", inputValue);

    axios.post(MYSCRIPT.ajaxUrl, params).then(function (responseAP) {
      console.log(responseAP.data);
    });
  };

  const handleChange = (e) => {
    setStatus(true);
    setInputValue(e.target.value);
  };

  const titleOnChange = (e) => {
    setTitle(e.target.value);
  };

  const slugOnChange = (e) => {
    setSlug(e.target.value);
  };

  const dynamicOnChange = (e) => {
    setDynamic(e.target.value);
  };

  useEffect(() => {
    fetch("https://tools.aeropage.io/api-connector/" + inputValue)
      .then((responseAP) => responseAP.json())
      .then((data) => setResponseAP(data));
  }, [inputValue]);

  useEffect(() => {
    if (responseAP?.status?.type === "success") setStatus(false);
    if (responseAP?.type === "PAGE_NOT_FOUND") setStatus(false);
  }, [responseAP]);

  console.log(responseAP);

  return (
    <div
      style={{
        background: "white",
        minHeight: "800px",
        height: "80vh",
        width: "100%",
      }}
    >
      <div style={{ display: "flex", justifyContent: "center" }}>
        <div
          style={{
            borderBottom: "1px solid lightGray",
            display: "flex",
            flexDirection: "row",
            paddingLeft: "15px",
            paddingRight: "15px",
            width: "100%",
          }}
        >
          <Header
            toolType={"Aeropage Plugin"}
            toolName={`${title}`}
            pathLevel={1}
            resetView={resetView}
          ></Header>

          <div
            style={{
              display: "flex",
              justifyContent: "center",
              width: "100%",
            }}
          ></div>
        </div>
      </div>

      <div
        style={{
          display: "flex",
          flexDirection: "row",
          width: "100%",
          justifyContent: "center",
        }}
      >
        <div
          style={{
            display: "flex",
            minWidth: "40%",
            flexDirection: "row",
            marginTop: "15px",
          }}
        >
          <div
            style={{
              padding: "25px 25px 25px 80px",
            }}
          >
            <p
              style={{
                color: "#595B5C",
                fontFamily: "'Inter', sans-serif",
                fontStyle: "normal",
                fontWeight: "600",
                fontSize: "14px",
                lineHeight: "120%",
              }}
            >
              Create Dynamic Pages
            </p>
            <p
              style={{
                color: "#595B5C",
                fontFamily: "'Inter', sans-serif",
                fontStyle: "normal",
                fontWeight: "400",
                fontSize: "10px",
                lineHeight: "175%",
              }}
            >
              We can now syncronize the data so you can use it in your website.
              Click the button below to begin syncronizing, and keep the window
              open while the process runs. It can take a little while depending
              how much data you have in the view.
            </p>
            <form onSubmit={handleSubmit}>
              <p
                style={{
                  color: "#595B5C",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "500",
                  fontSize: "12px",
                  lineHeight: "175%",
                  marginBottom: "6px",
                  marginTop: "14px",
                }}
              >
                Title
              </p>
              <input
                value={title}
                onChange={titleOnChange}
                style={{
                  height: "32px",
                  padding: "7px 10px 7px 10px",
                  borderRadius: "6px",
                  backgroundColor: "#F4F5F8",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  fontSize: "12px",
                  lineHeight: "150%",
                  border: "none",
                }}
                placeholder="Title *"
              ></input>
              <p
                style={{
                  color: "#595B5C",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "500",
                  fontSize: "12px",
                  lineHeight: "175%",
                  marginBottom: "6px",
                  marginTop: "25px",
                }}
              >
                Dynamic URL
              </p>
              <input
                value={slug}
                onChange={slugOnChange}
                style={{
                  height: "32px",
                  padding: "7px 10px 7px 10px",
                  borderRadius: "6px",
                  backgroundColor: "#F4F5F8",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  fontSize: "12px",
                  lineHeight: "150%",
                  border: "none",
                }}
                placeholder="Dynamic URL"
              ></input>{" "}
              /{" "}
              <select
                value={dynamic}
                onChange={dynamicOnChange}
                style={{
                  height: "32px",
                  borderRadius: "6px",
                  backgroundColor: "white",
                  color: "#595B5C",

                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  width: "100px",
                  border: "1px solid lightGray",
                  fontSize: "12px",
                  lineHeight: "18px",
                }}
              >
                <option
                  style={{
                    borderRadius: "6px",
                    color: "#595B5C",
                    fontFamily: "'Inter', sans-serif",
                    fontStyle: "normal",
                    fontWeight: "400",
                    fontSize: "12px",
                    lineHeight: "150%",
                  }}
                  value="record_id"
                >
                  [record_id]
                </option>
                <option
                  style={{
                    borderRadius: "6px",
                    color: "#595B5C",
                    fontFamily: "'Inter', sans-serif",
                    fontStyle: "normal",
                    fontWeight: "400",
                    fontSize: "12px",
                    lineHeight: "150%",
                  }}
                  value="name"
                >
                  [name]
                </option>
              </select>
              <p
                style={{
                  color: "#595B5C",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "500",
                  fontSize: "12px",
                  lineHeight: "175%",
                  marginBottom: "3px",
                  marginTop: "25px",
                }}
              >
                API Token
              </p>
              <p
                style={{
                  color: "#595B5C",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  fontSize: "10px",
                  lineHeight: "175%",
                  marginTop: "0",
                  marginBottom: "6px",
                }}
              >
                To create a connection please{" "}
                <a
                  style={{ textDecoration: "none" }}
                  target="_blank"
                  href="https://tools.aeropage.io/api-connector/"
                >
                  click here...
                </a>
              </p>
              <input
                value={inputValue}
                onChange={handleChange}
                style={{
                  height: "32px",
                  padding: "7px 10px 7px 10px",
                  borderRadius: "6px",
                  backgroundColor: "#F4F5F8",
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "400",
                  fontSize: "12px",
                  lineHeight: "150%",
                  border: "none",
                  marginBottom: "10px",
                  width: "260px",
                }}
                placeholder="Token"
              ></input>
              <div style={{ minHeight: "70px" }}>
                {responseAP?.status?.type === "success" && status === false ? (
                  <div
                    style={{
                      display: "flex",
                      flexDirection: "row",
                      alignItems: "center",
                    }}
                  >
                    {tickIcon}

                    <p
                      style={{
                        color: "#22BB33",
                        fontFamily: "'Inter', sans-serif",
                        fontStyle: "normal",
                        fontWeight: "500",
                        fontSize: "12px",
                        lineHeight: "24px",

                        margin: "0 0 0 5px",
                      }}
                    >
                      Success
                    </p>
                  </div>
                ) : null}

                {responseAP?.type === "PAGE_NOT_FOUND" && status === false ? (
                  <>
                    <p
                      style={{
                        fontFamily: "'Inter', sans-serif",
                        fontStyle: "normal",
                        fontWeight: "500",
                        fontSize: "12px",
                        lineHeight: "24px",
                        color: "red",
                        margin: "0 0 0 0",
                      }}
                    >
                      {responseAP?.source + " "}
                      {responseAP?.type}
                    </p>

                    <p
                      style={{
                        fontFamily: "'Inter', sans-serif",
                        fontStyle: "normal",
                        fontWeight: "400",
                        lineHeight: "175%",
                        color: "red",
                        fontSize: "10px",
                        margin: "0 0 0 0",
                      }}
                    >
                      {responseAP?.description}
                    </p>
                    <p
                      style={{
                        fontFamily: "'Inter', sans-serif",
                        fontStyle: "normal",
                        fontWeight: "400",
                        fontSize: "10px",
                        lineHeight: "175%",
                        color: "red",
                        margin: "0 0 0 0",
                      }}
                    >
                      {responseAP?.message}
                    </p>
                  </>
                ) : null}

                {status && inputValue ? (
                  <>
                    <div style={{ display: "flex", flexDirection: "row" }}>
                      <Oval
                        height={15}
                        width={15}
                        color="#4fa94d"
                        wrapperStyle={{}}
                        wrapperClass=""
                        visible={true}
                        ariaLabel="oval-loading"
                        secondaryColor="#4fa94d"
                        strokeWidth={2}
                        strokeWidthSecondary={2}
                      />
                      <span
                        style={{
                          color: "#595B5C",
                          fontFamily: "'Inter', sans-serif",
                          fontStyle: "normal",
                          fontWeight: "400",
                          marginTop: "2.5px",
                          fontSize: "10px",
                          lineHeight: "175%",
                        }}
                      >
                        Checking
                      </span>
                    </div>
                  </>
                ) : // <p
                //   style={{
                //     color: "#595B5C",
                //     fontFamily: "'Inter', sans-serif",
                //     fontStyle: "normal",
                //     fontWeight: "400",
                //     fontSize: "10px",
                //     lineHeight: "175%",
                //     margin: "0 0 0 0",
                //   }}
                // >
                //   Checking
                // </p>
                null}
              </div>
              {/* <Link to="/"> */}
              <button
                disabled={
                  !responseAP?.status?.type === "success" ||
                  dynamic === null ||
                  dynamic === "" ||
                  title === null ||
                  title === ""
                }
                style={{
                  fontFamily: "'Inter', sans-serif",
                  fontStyle: "normal",
                  fontWeight: "500",
                  fontSize: "12px",
                  lineHeight: "24px",
                  cursor: "pointer",
                  background:
                    responseAP?.status?.type === "success" &&
                    !(dynamic === null || dynamic === "") &&
                    !(title === null || title === "")
                      ? "#633CE3"
                      : "#bbaaf3",
                  color: "white",
                  padding: "8px 13px 8px 13px",
                  border: "none",
                  borderRadius: "6px",
                }}
                // onClick={() => {
                //   handleMyClick();
                // }}
              >
                Edit a Post
              </button>
              {/* </Link> */}
            </form>
          </div>

          <div
            style={{
              marginTop: "50px",
              marginBottom: "50px",
              minWidth: "60%",
              maxWidth: "60%",
              display: "flex",
              flexDirection: "row",
              justifyContent: "center",
            }}
          >
            {" "}
            <div
              style={{
                boxShadow: "0px 0px 10px -4px rgba(66, 68, 90, 1)",
                width: "500px",
                padding: "20px 20px 20px 20px",
                borderRadius: "6px",
                maxHeight: "600px",

                overflow: "scroll",
              }}
            >
              {responseAP ? <ReactJson src={responseAP} /> : null}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EditPost;
