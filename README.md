# 1AM — 시안 1 (Vivid)

1AM Slim HYBRID 브랜드 사이트 **워드프레스 테마** 시안 1.
fogformulas.com 처럼 맛마다 화면 전체 컬러가 바뀌고, 스크롤에 반응하는 인터랙션을 넣은 비비드 컨셉입니다.

```
1am-vivid/          ← 워드프레스 테마 (이 폴더를 wp-content/themes/ 에 업로드)
dist/1am-vivid.zip  ← 관리자 > 외모 > 테마 > 새로 추가 > 테마 업로드 용 zip
preview/index.html  ← 워드프레스 없이 브라우저로 열어보는 메인 정적 미리보기
tools/              ← 미리보기 생성 스크립트
```

## 설치

1. 워드프레스 관리자 → **외모 > 테마 > 새로 추가 > 테마 업로드** → `dist/1am-vivid.zip` 업로드 → 활성화
2. 바로 메인에 15개 맛이 나옵니다 (테마 기본 데이터).
3. 필요하면 **외모 > 사용자 정의하기 > 1AM 설정** 에서 문구 수정.

## 메인 페이지 구성 (심플 버전)

| 순서 | 섹션 | 내용 / 인터랙션 |
|---|---|---|
| – | 상단 고정 경고문 + 흰색 블러 헤더 | Products(드롭다운) · About · How to Order · FAQ · Log in · Apply for wholesale |
| 1 | 영상 (첫 화면) | 전체 화면 영상 + 은은한 어두운 톤 + 하단 한 줄 흰 글씨 + 소리 버튼 + 도매 신청 버튼 |
| 2 | 라인업 + About | 제품이 촘촘히 일자로 선 채 위에서 내려와 → 쫙 펼쳐지고 → 왼쪽으로 계속 흐름(호버하면 멈추고 맛 이름 표시). 파스텔 원이 꽃처럼 퐁퐁 피어나고 아래에 소개 문구 + About → 버튼 |
| 3 | 상품 3종 (가로 스크롤) | Slim HYBRID 2ml / HYBRID Max / HYBRID Refill |
| 4 | Slim outside. Loud inside. | 디바이스 + 스펙 |
| 5 | Stock 1AM. | 도매 가입 유도 (가입 / 로그인) |

**페이지별 역할 (중복 없이)**
- **상품 페이지 (Slim HYBRID)**: 맛 슬라이더(기존 첫 화면) → 상품 정보·스펙 → 맛 목록(필터) → 가입 유도
- **About**: 회사 소개(본문) → Why stock 1AM (장점 4개) → 가입 유도
- **How to Order**: 3단계 절차 → 본문 → FAQ 미리보기 → 가입 유도
- **FAQ**: 세부 정보(Details) 블록 아코디언

**영상 넣기**: `1am-vivid/assets/video/brand.mp4` (+ `brand.jpg`) 에 넣으면 자동 사용. 운영 시에는 사용자 정의하기 > 1AM Settings > Home video 에서 업로드.

**회원 상태별 버튼**: 비회원 → `Apply for wholesale` / 승인 대기 → `Application under review` / 승인 완료 → `Shop wholesale`.
승인 판단: 사용자 메타 `oneam_wholesale_status = approved` 또는 `wholesale_customer` 역할. 가입 승인 플러그인을 쓰면 `oneam_member_state` 필터로 연결합니다.

**WooCommerce**: 승인 회원이 아니면 상점·장바구니·결제 접근 시 가입(또는 승인 대기) 페이지로 이동, 쇼핑몰은 검색 노출 제외(noindex). 쇼핑몰 페이지에서는 부드러운 스크롤·커스텀 커서를 끄고 `woo.css` 로 브랜드 스타일만 입힙니다.

사이트 문구와 관리자 라벨은 모두 영어(캐나다 표기: flavour, colour)입니다.

제목 문구에서 `*단어*` 로 감싸면 세리프 이탤릭(Instrument Serif)으로 표시됩니다. 예: `Stocked for *retail.*`

## 상품 관리 = WooCommerce 하나로

테마 전용 상품 메뉴는 없습니다. **WooCommerce → Products** 에서만 관리하면 사이트 전체에 반영됩니다.

| WooCommerce 에서 | 사이트에 나오는 곳 |
|---|---|
| 상품 (이름 / 짧은 설명 / 대표 이미지 / 순서) | 메인 "상품 라인업" 가로 스크롤 |
| General 탭 **1AM homepage** 칸 (Label, Coming soon, 색 2개) | 라인업 패널의 라벨·출시 예정 표시·배경색 |
| 옵션이 아닌 속성 (예: E-liquid: 2ml) | 라인업·상품 페이지의 스펙 |
| 옵션 상품의 **옵션(맛)** — 이름·사진·설명 + **1AM colour** | 메인 15개 라인업 애니메이션, 상품 페이지 맛 슬라이더·맛 목록 |

- **상품 페이지는 공개**: 맛 슬라이더 → 상품 정보 → 맛 목록 → 가입 유도. 가격·장바구니 버튼은 승인 회원에게만 보이고, 비회원에게는 가입/로그인 버튼이 나옵니다.
- **상점 목록·장바구니·결제는 비공개** (승인 회원만).
- 출시 예정 상품은 Published + Catalog visibility "Hidden" + Coming soon 체크 → 라인업에만 나오고 상점 목록엔 안 나옵니다.
- WooCommerce 에 상품이 하나도 없으면 테마 기본 데이터(맛 15개, 상품 3종)가 대신 보입니다.
- `woocommerce/1am-products-import.csv` 를 **상품 → 가져오기** 로 올리면 위 설정이 다 된 상태로 3개 상품 + 맛 15개가 들어옵니다.
- 사이트 공개 전에는 WooCommerce → Settings → **Site visibility → Live** 로 바꿔야 상품 페이지가 방문자에게 보입니다 ("Coming soon" 상태에서는 관리자만 보임).

## 편집기 = 사이트와 같은 모양

- 페이지/글 편집기와 WooCommerce 상품 설명 편집기에서 글꼴·제목·이탤릭·버튼이 사이트와 똑같이 보입니다.
- 편집기 **+ → Patterns → 1AM** 에서 섹션을 클릭 한 번으로 넣을 수 있습니다: Colour header / Four benefit cards / Three steps / FAQ / Wholesale call to action. 넣은 뒤 글자만 바꾸면 됩니다.
- 색상 선택 칸에 1AM 색(Grape, Mint, Watermelon…)과 그라데이션이 들어 있습니다.

## 만들어야 할 페이지 (관리자 > 페이지)

| 페이지 | 슬러그 | 템플릿 |
|---|---|---|
| About | `about-us` | About Us (본문은 블록 편집기, 1AM 패턴 사용 가능) |
| How to Order | `how-to-order` | How to Order (구매 절차) |
| FAQ | `faq` | 기본 — 질문마다 **세부 정보(Details)** 블록 사용 |
| Wholesale Sign up | `wholesale-signup` | 기본 — 소개 문구만 쓰면 아래에 가입 폼이 자동으로 붙음 |
| Terms / Privacy / Shipping & Returns | `terms` · `privacy-policy` · `shipping-returns` | 기본 |

## 도매 회원 가입 · 승인

1. **가입**: `/wholesale-signup/` 페이지에 신청 폼이 자동으로 붙습니다 (다른 페이지에 넣으려면 `[oneam_wholesale_signup]` 숏코드).
   항목: 사업자명, 사업자 번호(BN), 매장 주소·도시·주·우편번호, 담당자, 전화, 이메일, 비밀번호, 사업자 서류(PDF/JPG/PNG, 10MB 이하), 19세·소매업 확인.
2. 신청하면 WooCommerce 고객 계정이 **Pending** 상태로 만들어지고, 관리자 이메일(설정 > 일반 > 관리자 이메일)로 알림이 갑니다. 신청자에게도 접수 메일.
3. **승인**: 관리자 **Users** (대기 건수 빨간 배지) → **Pending wholesale** 필터 → 신청자 줄에 마우스 → **View document** 로 서류 확인 → **Approve** 또는 **Reject**. 결과 메일이 자동 발송됩니다.
4. 사용자 편집 화면 아래 **1AM wholesale** 칸에서 사업자 정보 수정·상태 변경도 가능.
5. 승인되면 가격·장바구니·상점·결제가 열리고 버튼이 `Shop wholesale` 로 바뀝니다. 대기·거절 상태는 가입 페이지로 안내.

- 서류는 `wp-content/uploads/oneam-wholesale-docs/` 에 무작위 이름으로 저장되고 `.htaccess` 로 직접 접근을 막습니다 (관리자만 View document 로 열람).
- 메일이 안 오면 SMTP 플러그인(예: WP Mail SMTP) 설치를 권장합니다. 호스팅 기본 메일은 스팸함으로 가는 경우가 많습니다.

## 기타 관리

- **메뉴**: 외모 > 메뉴 → Header menu / Footer menu / Footer policies menu (없으면 기본 메뉴)
- **로고**: 사용자 정의하기 > 사이트 아이덴티티 > 로고
- **문구**: 사용자 정의하기 > 1AM Settings (영상, 소개 문구, 스펙, CTA, 경고 문구, 성인 인증)

## 참고 / 확인 필요

- 회사 소개, 장점 4개, FAQ 답변, 상품 2·3 이름(HYBRID Max / HYBRID Refill)과 스펙, 디바이스 콜아웃은 **임시 문구**입니다.
- 상단 경고문 문구는 Health Canada 기준으로 최종 확인이 필요합니다.
- 맛별 컬러는 제품 이미지에서 뽑아 채도를 올린 값입니다. 관리자에서 바로 바꿀 수 있습니다.
- 라이브러리(GSAP 3.15, ScrollTrigger, Lenis 1.3)와 폰트(Archivo, Instrument Serif)는 테마 안에 포함되어 있어 외부 CDN 없이 동작합니다.

## 미리보기 다시 만들기

```bash
php tools/preview-shim.php 1am-vivid front-page.php > preview/index.html
```
